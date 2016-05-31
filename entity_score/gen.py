import json

def getContext(tweet):
    tweet_words = tweet.split(' ')
    for i in range(len(tweet_words)):
        if tweet_words[i][0] == '@' or tweet_words[i].startswith('http'):
            pass
        else:
            return ' '.join(tweet_words[i:i + 4])

for i in range(0, 51):
    f = open('../data/tree_info' + str(i) + '.json', 'r')
    root = json.load(f)
    print '      <tr>\n        <td><a href=index.html?cascade=' + str(i) + '>Cascade ' + str(i) + '</a></td>\n        <td>' + getContext(root['tweet'].encode('ascii', 'ignore').strip()) + '</td>\n      </tr>'
    f.close()
