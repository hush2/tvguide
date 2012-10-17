#
# Copyright (C) 2012 hush2 <hushywushy@gmail.com>
#

require 'rubygems'
require 'net/http'
require 'uri'
require 'nokogiri'
require 'erb'

def get_files

  time = Time.now
  
  today = time.strftime "%Y-%m-%d"
  
  hour = time.hour.odd? ? time.hour - 1 : time.hour
  
  (hour..24).step(2).each do |hour|
  
    $filename = sprintf '%s_%02i.htm', today, hour
    puts "Downloading #{$filename}"
    next if File.exist? $filename
    now = sprintf "%02i:00:00", hour
    begin
      uri = URI.parse('http://www.clickthecity.com/tv/main.php')
      dl = Net::HTTP.post_form(uri,
      #dl = ClickTheCity.post('/tv/main.php',
                 #:body => {'optDate' => today,
              {'optDate' => today,
               'btnLoad' => 'Go',
               'optCable' => '8',
               'optTime' => now})
      File.open($filename, 'w') { |f| f.puts dl.body }
    rescue #Errno::ECONNREFUSED => e
      #puts "Err: #{e.message}"
      print "\nConnection error...."
      gets
      exit 23
    end
  end
end

$networks = ['HBO', 'PBO', 'JackTV', 'Disney Channel', 'Nickelodeon', 'National Geographic', 'Discovery Channel', 'The Game Channel']

$shows  = {}

def parse_files
  time = Time.now
  today = time.strftime "%Y-%m-%d"
  hour = time.hour.odd? ? time.hour + 1 : time.hour

  (hour..24).step(2).each do |i|
    
    $filename = sprintf '%s_%02i.htm', today, i
    puts "Parsing #{$filename}"
    file = File.open($filename) { |f| f.read }
    html = Nokogiri::HTML(file)
    tr = html.search '//tr'
    $networks.each do |network|
      tr.each do |t|
        $shows[network] ||= []
        alt = t.search "img[@alt='#{network}']"
        next if alt.empty?
        td = alt[0].parent.parent.parent.search 'td'
        $channel = td[1].text
        li = td[3].search 'ul/li/div/a'
        li.each do |a|
          startime = a['href'].split('&')[1].split('=')[1].gsub('%3A', ':').gsub('+', ' ')
          unless $shows[network].include? [startime, a.text]
            $shows[network] << [startime, a.text]
          end
        end; end;end; end;
end

if $0 == __FILE__

  get_files
  parse_files

  template = File.open('tvguide.erb').read

  html = ERB.new template

  networks = $networks
  shows = $shows

  file = Time.now.strftime "%Y-%m-%d" + '.htm'
  File.open(file, 'w') do |f|
    html = html.result binding
    f.puts html
  end

end
