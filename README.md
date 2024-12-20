# PlexAmpPlaylistCreator
This is a script that will take the existing play queue of any PlexAmp player on the network, and instantly turn it into a playlist named "automagically created" (or whatever you choose to have it named).  If the named playlist already exists when this script is run, it will delete it first, then create a new one (this is because there is no "clear playlist" function, so it has to be deleted and then recreated).


**************** Here is the backstory of why this was created, with a detailed explanation further below (so you can skip this, but it'll help understand what the script does): ***********

I have grouped Alexa speakers in my home, and I wanted to be able to use PlexAmp to play to those speakers.  At present, the only "alexa skill" that works with Plex, is the "Plex Skill".  The only function that works with Alexa to get her to play music is to either "Alexa, ask Plex to play (specific song here)" OR to ask "Alexa, ask Plex to play (specific playlist here)". 

This meant what I was stuck doing was selecting a "radio station" within PlexAmp on my phone (like "library radio" or "style radio" or even having it play music based on sonically similar songs to a chosen song).  PlexAmp then creates a "play queue" on the fly.  I'd then have to go and save that play queue as a playlist, and then ask Alexa to play that playlist.  What's worse, is Alexa doesn't recognize all "playlist titles" effectively, so I got "automagically created" as a playlist title to work 100% of the time when asking her.  So if I'd already created a playlist with that name, since there's no "clear playlist" function, anytime I wanted to play new music on my Alexa speakers, I'd have to delete that playlist before re-creating it with a new play queue.

I've got this script paired with HomeAssistant, and I created a "fake" PlexAmp Player on my network.  Personally I'm running my server on Linux, so I installed "PlexAmp Headless" and whenever HomeAssistant see's that I've "flung" music to that player (which has no output, I don't have speakers on the server), it then runs this script to take that players current play queue and re-creates the "automagically created" playlist, and then sends a command programmatically to my Alexa group to "Ask Plex to play automagically created".

So literally all I have to do, is open PlexAmp on my phone, select the "fake" PlexAmp player and start a "radio station" and ten to fifteen seconds later, that station begins playing on my Alexa speakers.

If you're looking to do something similar, this will work with any type of PlexAmp installation on any device (iPad, Windows, Linux, whatever).  You just need to make sure that player has "remote control" enabled in the settings (settings -> playback -> remote control).  On the "remote control" screen you'll be able to get that players IP and port.  I recommend setting a static IP and port.




****** SPECIFICALLY, HERE IS WHAT THE SCRIPT DOES *************

It's very basic.  Whenever this script is triggered (you can trigger it however you'd like, including manually), it will reach out to your Plex server and look for the playlist name that's defined in the settings.  In this case "automagically created" is the default.  If it exists, it will delete it.  Then, it will reach out to the PlexAmp player IP that you've defined and take whatever play queue is on that device, and turn it into a playlist named "automagically created" (or whatever you choose, that's the default).

That's it, that's all it does.

It's important that the PlexAmp IP address you're defining, is that of the player that it's actually playing on.  If you're simply using PlexAmp on your phone to control a PlexAmp player on your desktop or iPad, you need to put the IP address of the device that you're casting to/remote controlling.