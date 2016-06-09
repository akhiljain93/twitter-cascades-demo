def getAllEntitiesAndUrls(cascade, filename):
    ret = ''
    for line in filename:
        entity = line.split(',')[1].split('.')[1]
        url = 'index.html?cascade=' + str(cascade) + '&scope=' + line.split(',')[1].split('.')[0]
        ret = ret + '<a href=' + url + '>' + entity + '</a> '
    return ret

def printCascadeInfo(i):
    global foreign_lang_cascades
    if i not in foreign_lang_cascades:
        f = open('reps/rep_' + str(i) + '.csv', 'r').read().splitlines()
        print '      <tr>\n        <td>' + str(i) + '</td>\n        <td><a href=index.html?cascade=' + str(i) + '&view=1>View 1</a> <a href=index.html?cascade=' + str(i) + '&view=2>View 2</a> <a href=index.html?cascade=' + str(i) + '&view=3>View 3</a></td>\n        <td>' + getAllEntitiesAndUrls(i, f[1:]) + '</td>\n      </tr>'
    else:
        print '      <tr>\n        <td>' + str(i) + '</td>\n        <td><a href=index.html?cascade=' + str(i) + '&view=1>View 1</a> <a href=index.html?cascade=' + str(i) + '&view=2>View 2</a> <a href=index.html?cascade=' + str(i) + '&view=3>View 3</a></td>\n        <td>Foreign Language Cascade</td>\n      </tr>'

foreign_lang_cascades = [3, 8, 15, 22, 33, 42]

print '<!DOCTYPE html>\n<html>\n  <head>\n    <meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\"/>\n  </head>\n  <body>\n    <h1>Twitter Cascade Summarisation</h1>\n    <table border=\"1\" style=\"width:49%; float: left\">\n      <tr>\n        <th>Cascade</th>\n        <th>Cascade views</th>\n        <th>Major entities</th>\n      </tr>'

for i in range(25):
    printCascadeInfo(i)

print '    </table>\n    <table border=\"1\" style=\"width:49%; float: left\">\n      <tr>\n        <th>Cascade</th>\n        <th>Cascade views</th>\n        <th>Major entities</th>\n      </tr>'

for i in range(25, 51):
    printCascadeInfo(i)

print '    </table>\n  </body>\n</html>'
