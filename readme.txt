This plugin creates a new page to display a very specific profile field for users within a specific usergroup. This includes additional usergroups and display uaergroups as well! 

Installation: 
    - Upload the contents of the "Upload" folder to your forum root. Activate and install from your ACP. 
    - This plugin creates a new "pfd_page" template in global templates. Modify as needed. 

To View: 
    - Go to http://example.com/misc.php?action=field-display
    
Configuration: 
    - To get your group ID, go to your ACP and edit a specific usergroup. Look for the "gid=somenumber" parameter in the URL. This is your group ID. 
    - This plugin will fetch all users in this group, including the display group and additional groups. 
    - To get your field ID, edit a specific profile field in your ACP. Look for "fid=somenumber" in the URL. 
    - You can replace "Field Data" text (on the page's table) with the "Field Name" setting that is added by this plugin. 

Additional info: 
    - This plugin has a $blank_ignore setting near the beginning of inc/plugins/pfield.php. This variable determines whether we should skip users whose specified profile field is blank. By default, this is false. 
    - PFD may be slower on large forums (10,000+ users) due to the search algorithm used in its database queries. This will only affect the PFD page and will not affect any other pages on your forum. 

License: GNU GPL, Version 3. 