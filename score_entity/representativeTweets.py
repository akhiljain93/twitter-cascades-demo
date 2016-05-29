import json
import math
import tagTweets
import re
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

def getScore(node):
    return node['user_score'] + 5 * node['entropy_score']

def parseTree(node):
    global tweet_list
    global tweetid_node_map

    if 'children' in node:
        for child in node['children']:
            parseTree(child)

    visit_entropy_and_user(node)
    
    tweet_list.append(node['id'])
    tweetid_node_map[node['id']] = node

def findEntities(tree):
    global tweet_list
    global tweetid_node_map

    parseTree(tree)
    tags = tagTweets.runtagger_parse([tweetid_node_map[tweetid]['tweet'] for tweetid in tweet_list])
    all_entities = []

    # create a joint entity model list for the cascade
    # all_entities contains [<entity name>, <max score tweet id>, <tweetids containing entity>]
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
                        all_entities[this_index][1] = all_entities[this_index][1] if getScore(tweetid_node_map[all_entities[this_index][1]]) > getScore(tweetid_node_map[tweet_list[tweet_index]]) else tweet_list[tweet_index]
                        all_entities[this_index][2] = all_entities[this_index][2].union(set([tweet_list[tweet_index]]))
                # single entity with no neighbour
                else:
                    present = False
                    # search if this entity already occurs as a component in some other previously seen entity
                    for entity_index in range(len(all_entities)):
                        if removePunctuation(tags[tweet_index][token_index][0].lower()) in all_entities[entity_index][0].split(' '):
                            present = True
                            all_entities[entity_index][1] = all_entities[entity_index][1] if getScore(tweetid_node_map[all_entities[entity_index][1]]) > getScore(tweetid_node_map[tweet_list[tweet_index]]) else tweet_list[tweet_index]
                            all_entities[entity_index][2] = all_entities[entity_index][2].union(set([tweet_list[tweet_index]]))
                            break

                    # never seen this entity before in any form
                    if not present:
                        all_entities.append([removePunctuation(tags[tweet_index][token_index][0].lower()), tweet_list[tweet_index], set([tweet_list[tweet_index]])])

    # set up all the entities for comparison
    for entity in all_entities:
        entity[0] = ' '.join(list(set(entity[0].split(' '))))

    # there will be some duplicate entities, remove them - not exact matches, but matches; we know what you mean! ;)
    entity_index1 = 0
    while entity_index1 < len(all_entities):
        entity_index2 = entity_index1 + 1
        while entity_index2 < len(all_entities):
            if len(all_entities[entity_index2][0].split(' ')) == len(all_entities[entity_index1][0].split(' ')):
                if all_entities[entity_index2][0] == all_entities[entity_index1][0]:
                    all_entities[entity_index1][1] = all_entities[entity_index1][1] if getScore(tweetid_node_map[all_entities[entity_index1][1]]) > getScore(tweetid_node_map[all_entities[entity_index2][1]]) else all_entities[entity_index2][1]
                    all_entities[entity_index1][2] = all_entities[entity_index1][2].union(all_entities[entity_index2][2])
                    del all_entities[entity_index2]
            elif len(all_entities[entity_index2][0].split(' ')) > len(all_entities[entity_index1][0].split(' ')):
                present = True
                for token in all_entities[entity_index1][0].split(' '):
                    if token not in all_entities[entity_index2][0].split(' '):
                        present = False
                        break

                if present:
                    all_entities[entity_index1][0] = all_entities[entity_index2][0]
                    all_entities[entity_index1][1] = all_entities[entity_index1][1] if getScore(tweetid_node_map[all_entities[entity_index1][1]]) > getScore(tweetid_node_map[all_entities[entity_index2][1]]) else all_entities[entity_index2][1]
                    all_entities[entity_index1][2] = all_entities[entity_index1][2].union(all_entities[entity_index2][2])
                    del all_entities[entity_index2]
            else:
                present = True
                for token in all_entities[entity_index2][0].split(' '):
                    if token not in all_entities[entity_index1][0].split(' '):
                        present = False
                        break

                if present:
                    all_entities[entity_index1][1] = all_entities[entity_index1][1] if getScore(tweetid_node_map[all_entities[entity_index1][1]]) > getScore(tweetid_node_map[all_entities[entity_index2][1]]) else all_entities[entity_index2][1]
                    all_entities[entity_index1][2] = all_entities[entity_index1][2].union(all_entities[entity_index2][2])
                    del all_entities[entity_index2]

            entity_index2 += 1
        entity_index1 += 1

    return (all_entities, tags)

def getContext(tags, topic, tweetid):
    global tweet_list
    this_tags = tags[tweet_list.index(tweetid)]

    topic_words = topic.split(' ')
    
    context = ''
    contains_topic_word = [False] * len(this_tags)
    count_topic_words = 0

    for i in range(len(this_tags)):
        for j in range(len(topic_words)):
            if topic_words[j] == removePunctuation(this_tags[i][0]).lower():
                contains_topic_word[i] = True
                count_topic_words += 1
    
    index_topic_word = 0
    for index_topic_word in range(len(this_tags)):
        if contains_topic_word[index_topic_word]:
            break
    if count_topic_words == 1:
        index_topic_word -= 2

    count_context_words = 0
    while count_context_words < 5 and index_topic_word < len(this_tags):
        if this_tags[index_topic_word][1] in ['@', 'U', ',']:
            pass
        elif this_tags[index_topic_word][1] in ['P', '&']:
            context += (this_tags[index_topic_word][0] + ' ')
        else:
            context += (this_tags[index_topic_word][0] + ' ')
            count_context_words += 1

        index_topic_word += 1

    return context.strip()

cascade = int(sys.argv[1])
entity_types = ['N', '^'] if '--common' in sys.argv else ['^']
alpha = 0.1

tweetid_node_map = {}
tweet_list = []

tree = json.load(open('../original_cascades/tree_info' + str(cascade) + '.json', 'r'))

(all_entities, tags) = findEntities(tree)
all_entities = sorted(all_entities, reverse = True, key = lambda x: len(x[2]))[:10]

rep_file = open('reps/rep_' + str(cascade) + '.csv', 'w')
rep_file.write('Context,Topic of discussion,Representative tweet\n')
for entity_index in range(len(all_entities)):
    entity = all_entities[entity_index]
    rep_file.write(getContext(tags, entity[0], entity[1]) + ',' + str(entity_index) + '.' + entity[0] + ',' + str(entity[1]) + '\n')
    all_entities[entity_index][2] = list(entity[2])
rep_file.close()

scope_file = open('scope/scope_' + str(cascade) + '.csv', 'w')
for entity in all_entities:
    scope_file.write(str(entity[2][0]))
    for tweetid in entity[2][1:]:
        scope_file.write(',' + str(tweetid))
    scope_file.write('\n')
scope_file.close()
