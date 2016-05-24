import json
import math
import tagTweets
import string
import sys

def removePunctuation(word):
    word = word.translate(None, string.punctuation)
    return word

def visit_entropy_and_user(node):
    global alpha
    node['tree_size'] = 1
    node['entropy_score'] = 0.0
    node['users'] = {}
    node['users'][node['user']] = 1
    node['user_score'] = 0.0
    if 'children' in node and len(node['children']) != 0:
        for child in node['children']:
            node['tree_size'] += child['tree_size']
            for user in child['users']:
                if user in node['users']:
                    node['users'][user] += child['users'][user]
                else:
                    node['users'][user] = child['users'][user]
        for child in node['children']:
            score_hx = child['tree_size'] / (node['tree_size'] - 1.0)
            node['entropy_score'] += (child['entropy_score'] - ((1 + (alpha if node['reply'] else 0)) * (score_hx * math.log(score_hx))))
        sum = 0
        for value in node['users'].itervalues():
            sum += value
        for value in node['users'].itervalues():
            user_hx = value / (sum * 1.0)
            node['user_score'] -= user_hx * math.log(user_hx)

def parseTree(node):
    global tweet_list

    if 'children' in node:
        for child in node['children']:
            parseTree(child)

    visit_entropy_and_user(node)

    tweet_list.append([node['tweet'], node['entropy_score'], node['user_score']])

cascade = int(sys.argv[1])
entity_types = ['N', '^'] if '--common' in sys.argv else ['^']
alpha = 0.1

tree = json.load(open('../tree_info' + str(cascade) + '.json', 'r'))

tweet_list = []
parseTree(tree)
tags = tagTweets.runtagger_parse([tweet[0] for tweet in tweet_list])
all_entities = []

# create a joint entity model list for the cascade
# all_entities contains [<entity name>, <entity count>, <entity entropy score>, <entity user score>, <max user score tweet id, score>, <max entropy score tweet id, score>]
for tweet_index in range(len(tags)):
    for token_index in range(len(tags[tweet_index])):
        if tags[tweet_index][token_index][1] in entity_types:
            # need to join the entities
            if token_index > 0 and tags[tweet_index][token_index - 1][1] == tags[tweet_index][token_index][1]:
                this_entity = removePunctuation((tags[tweet_index][token_index - 1][0] + ' ' + tags[tweet_index][token_index][0]).lower())
                # first time we are encountering the joint entity
                if not this_entity in [x[0] for x in all_entities]:
                    for entity_index in range(len(all_entities)):
                        if removePunctuation(tags[tweet_index][token_index - 1][0].lower()) in all_entities[entity_index][0].split(' '):
                            all_entities[entity_index][0] = removePunctuation((all_entities[entity_index][0] + ' ' + tags[tweet_index][token_index][0]).lower())
                else:
                    this_index = [x[0] for x in all_entities].index(this_entity)
                    all_entities[this_index][1] += 1
                    all_entities[this_index][2] += tweet_list[tweet_index][1]
                    all_entities[this_index][3] += tweet_list[tweet_index][2]
                    all_entities[this_index][4] = all_entities[this_index][4] if all_entities[this_index][4][1] > tweet_list[tweet_index][2] else (tweet_list[tweet_index][0], tweet_list[tweet_index][2])
                    all_entities[this_index][5] = all_entities[this_index][5] if all_entities[this_index][5][1] > tweet_list[tweet_index][1] else (tweet_list[tweet_index][0], tweet_list[tweet_index][1])
            # single entity with no neighbour
            else:
                present = False
                # search if this entity already occurs as a component in some other previously seen entity
                for entity_index in range(len(all_entities)):
                    if removePunctuation(tags[tweet_index][token_index][0].lower()) in all_entities[entity_index][0].split(' '):
                        present = True
                        all_entities[entity_index][1] += 1
                        all_entities[entity_index][2] += tweet_list[tweet_index][1]
                        all_entities[entity_index][3] += tweet_list[tweet_index][2]
                        all_entities[entity_index][4] = all_entities[entity_index][4] if all_entities[entity_index][4][1] > tweet_list[tweet_index][2] else (tweet_list[tweet_index][0], tweet_list[tweet_index][2])
                        all_entities[entity_index][5] = all_entities[entity_index][5] if all_entities[entity_index][5][1] > tweet_list[tweet_index][1] else (tweet_list[tweet_index][0], tweet_list[tweet_index][1])
                        break

                # never seen this entity before in any form
                if not present:
                    all_entities.append([removePunctuation(tags[tweet_index][token_index][0].lower()), 1, tweet_list[tweet_index][1], tweet_list[tweet_index][2], (tweet_list[tweet_index][0], tweet_list[tweet_index][2]), (tweet_list[tweet_index][0], tweet_list[tweet_index][1])])

# there will be some duplicate entities, remove them - not exact matches, but matches; we know what you mean! ;)
entity_index1 = 0
while entity_index1 < len(all_entities):
    entity_index2 = entity_index1 + 1
    while entity_index2 < len(all_entities):
        if len(all_entities[entity_index2][0].split(' ')) == len(all_entities[entity_index1][0].split(' ')):
            if all_entities[entity_index2][0] == all_entities[entity_index1][0]:
                all_entities[entity_index1][1] += all_entities[entity_index2][1]
                all_entities[entity_index1][2] += all_entities[entity_index2][2]
                all_entities[entity_index1][3] += all_entities[entity_index2][3]
                all_entities[entity_index1][4] = all_entities[entity_index1][4] if all_entities[entity_index1][4][1] > all_entities[entity_index2][4][1] else all_entities[entity_index2][4]
                all_entities[entity_index1][5] = all_entities[entity_index1][5] if all_entities[entity_index1][5][1] > all_entities[entity_index2][5][1] else all_entities[entity_index2][5]
                del all_entities[entity_index2]
        elif len(all_entities[entity_index2][0].split(' ')) > len(all_entities[entity_index1][0].split(' ')):
            present = True
            for token in all_entities[entity_index1][0].split(' '):
                if token not in all_entities[entity_index2][0].split(' '):
                    present = False
                    break

            if present:
                all_entities[entity_index1][0] = all_entities[entity_index2][0]
                all_entities[entity_index1][1] += all_entities[entity_index2][1]
                all_entities[entity_index1][2] += all_entities[entity_index2][2]
                all_entities[entity_index1][3] += all_entities[entity_index2][3]
                all_entities[entity_index1][4] = all_entities[entity_index1][4] if all_entities[entity_index1][4][1] > all_entities[entity_index2][4][1] else all_entities[entity_index2][4]
                all_entities[entity_index1][5] = all_entities[entity_index1][5] if all_entities[entity_index1][5][1] > all_entities[entity_index2][5][1] else all_entities[entity_index2][5]
                del all_entities[entity_index2]
        else:
            present = True
            for token in all_entities[entity_index2][0].split(' '):
                if token not in all_entities[entity_index1][0].split(' '):
                    present = False
                    break

            if present:
                all_entities[entity_index1][1] += all_entities[entity_index2][1]
                all_entities[entity_index1][2] += all_entities[entity_index2][2]
                all_entities[entity_index1][3] += all_entities[entity_index2][3]
                all_entities[entity_index1][4] = all_entities[entity_index1][4] if all_entities[entity_index1][4][1] > all_entities[entity_index2][4][1] else all_entities[entity_index2][4]
                all_entities[entity_index1][5] = all_entities[entity_index1][5] if all_entities[entity_index1][5][1] > all_entities[entity_index2][5][1] else all_entities[entity_index2][5]
                del all_entities[entity_index2]

        entity_index2 += 1
    entity_index1 += 1

for entity in all_entities:
    entity[0] = ' '.join(list(set(entity[0].split(' '))))

all_entities = sorted(all_entities, reverse = True, key = lambda x: x[1])[:10]

print 'Topic of discussion,Representative tweet,Another representative tweet'
for entity in all_entities:
    if entity[4][0] != entity[5][0]:
        print '\"' + entity[0] + '\",\"' + entity[4][0].encode('ascii', 'ignore').strip().replace('\"', '\'') + '\",\"' + entity[5][0].encode('ascii', 'ignore').strip().replace('\"', '\'') + '\"'
    else:
        print '\"' + entity[0] + '\",\"' + entity[4][0].encode('ascii', 'ignore').strip().replace('\"', '\'') + '\"'
