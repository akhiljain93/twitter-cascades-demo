import json

for i in range(0, 51):
  f = open('/home/dual/cs5110274/public_html/data/tree_info' + str(i) + '.json', 'r')
  root = json.load(f)
  print '      <tr>\n        <td><a href=http://www.cse.iitd.ac.in/~cs5110274/entity_score/?cascade=' + str(i) + '>Cascade ' + str(i) + '</a></td>\n        <td>' + root['tweet'].encode('ascii', 'ignore').strip() + '</td>\n      </tr>'
  f.close()
