from BeautifulSoup import BeautifulSoup
import httplib2
import urllib
import datetime
import os
import codecs
import time

def get_files():
    data={}
    dt=datetime.datetime.now()
    today='%s-%02i-%02i' % (dt.year, dt.month, dt.day)
    data['btnLoad']='Go'
    data['optTime']=''
    data['optDate']=today
    data['optCable']='8'


    global hour
    hour = datetime.datetime.now().hour
    if hour % 2 != 0:
        hour -= 1

    for i in range(hour, 24, 2):

        if os.path.exists('%s_%02i.htm' % (today, i)):
        	continue

        data['optTime']='%02i:00:00' % i

        body = urllib.urlencode(data)

        h = httplib2.Http()
        url="http://www.clickthecity.com/tv/main.php"
        headers = {'Content-type': 'application/x-www-form-urlencoded'}

        print 'downloading %s_%02i.htm' % (today, i)

        resp, content = h.request(url, method="POST", body=body,headers=headers)


        f=open('%s_%02i.htm' % (today, i),'w')
        f.write(content)
        f.close()


def parse_file(soup, networks):
    global d
    for network, chan in networks:
        alt=soup.find('img',alt=network)
        if alt==None:
            continue
        li=alt.parent.parent.parent.findAll('li')

        for i in li:
            a=i.find('a',href=True)
            if a != None:

                showname=a.string
                showtime= a['href'].split('&')[1].split('=')[1].replace('%3A',':').replace('+',' ')

                if (showtime,showname) not in d[network]:
                    d[network].append((showtime,showname))
                #~ d[network].append((showtime,showname))

def main():
    get_files()
    global d
    head= '<html><head><title>%s</title></head><body><table border="1">'


    head+=  time.strftime('<h1>%B %d, %Y (%A)<h1>',time.localtime())


    title='<tr><th colspan="2">%s (%s)</th></tr>'
    table='<tr><td>%s</td><td>%s</td></tr>'
    table2='<tr><td style="background-color: #c0c0c0">%s</td><td>%s</td></tr>'

    foot='</table></body></html>'
    tables=''

    networks = [('HBO', 39), ('PBO', 38), ('JackTV', 30), ('Disney Channel', 50), ('ETC', 28), ('2nd Avenue', 29), ('National Geographic', 55), ('Discovery Channel', 56), ('bTV', 34)]

    for network, chan in networks:
        d[network] = []

    dt=datetime.datetime.now()
    today = '%s-%02i-%02i' % (dt.year, dt.month, dt.day)

    for i in range(hour, 24, 2):

        file = '%s_%02i.htm' % (today, i)
        soup = BeautifulSoup(open(file).read())
        print 'parsing', file

        parse_file(soup, networks)

    for network, chan in networks:
        tables += title % (network, chan)
        for showtime,showname in d[network]:
            if showtime[-2:] == 'PM':
                tables += table2 % (showtime, showname)
            else:
                tables += table % (showtime, showname)

    f=codecs.open('%s.htm' % today,'w','utf-8')
    f.write(head %datetime.datetime.now() + tables + foot)



d = {}
main()
hour = 0
import sys
sys.exit(23)