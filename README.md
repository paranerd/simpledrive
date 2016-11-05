# Requirements

- apache2
- mysql-server
- php5
- php5-gd
- php5-mysql

# Setup

Just fork this git or download as zip to your server directory.

Enable htaccess by setting AllowOverride All in your apache2.conf

Enable mod_rewrite by executing:
$ sudo a2enmod rewrite
$ sudo apache2 restart

Then, in your browser, navigate to the setup.php, fill out the required fields and you are good to go.

simpleDrive is made to be highly intuitive, so if you ever used any file manager, you should be comfortable right away.

### Nginx

In case you run on an nginx-server, please add the following to your nginx.conf (change server_name and root to match your setup)

	server {
		listen 80;
		server_name localhost;

		root /var/www/html/simpledrive;
		index index.php;

		location ~ ^/(?:\.htaccess|docs|config|logs){
			deny all;
		}

		location ~* \.(?:css|js|woff|ico)$ {
			add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;";
			add_header X-Content-Type-Options nosniff;
			add_header X-Frame-Options "SAMEORIGIN";
			add_header X-XSS-Protection "1; mode=block";
			add_header X-Robots-Tag none;
		}

		location / {
			try_files $uri $uri/ @w;
		}

		location @w {
			rewrite ^/api/(.*)$ /php/api.php?request=$1 last;
			rewrite ^/webdav(.*)$ /php/webdav.php last;
			rewrite ^/(.*)$ /index.php?request=$1 last;
		}

		location ~ \.php$ {
			include snippets/fastcgi-php.conf;
			fastcgi_pass unix:/run/php/php5-fpm.sock;
			# or fastcgi_pass unix:/run/php/php7.0-fpm.sock;
		}
	}

# API Usage

Call structure: [server]/api/[endpoint]/[action]

All values are returned as JSON-Array ('msg' => $msg)

## Core

#### login
	params:		username
				password

	returns:	auth token

#### setup
	params:		username
				password
				database username
				database password
				database server (opt)
				database name (opt)
				mail (opt)
				mail password (opt)

	returns:	null


## Files
#### children
	desc:		returns directory content or shares from database

	params:		auth token
				target			-> array of relative path and share id (if any)
				mode			-> accepted values: files | sharedbyme | sharedwithme | trash

	returns:	list of files/directories

#### create
	desc:		creates file/folder

	params:		auth token
				target			-> ID of directory to create in
				type			-> accepted values: file | folder
				filename (opt)	-> default given if not set

	returns:	null

#### rename
	desc:		renames file(s)

	params:		auth token
				target			-> ID
				newFilename

	returns:	null

#### copy
	desc:		copies file(s)

	params:		auth token
				target			-> ID of directory to copy to
				source			-> ID(s) of element(s) to copy

	returns:	null

#### move
	desc:		moves file(s)

	params:		auth token
				target			-> ID of directory to move to
				source			-> ID(s) of element(s) to move
				trash			-> true if restore from trash

	returns:	status message including number of moved files

#### delete
	desc:		deletes files/directories or moves them to trash

	params:		auth token
				target			-> ID(s) of element(s) to delete

	returns:	null

#### zip
	desc:		creates a zip archive

	params:		auth token
				target			-> ID of directory to zip to
				source			-> ID(s) of element(s) to zip

	returns:	destination of zipped file

#### share
	desc:		shares an element with another user and/or via link

	params:		auth token
				target			-> ID of element to share
				userto			-> user to share with
				mail (opt)		-> mail for notification
				write			-> true for write permission
				pubAcc			-> true for public access
				key (opt)		-> to protect share with password

	returns:	null or public link

#### unshare
	desc:		removes the share entry

	params:		token
				target			-> ID of element to unshare

	returns:	null

#### getlink
	desc:		returns the public link of an element

	params:		token
				target			-> ID of shared element

	returns:	link

### get
	desc:		returns a file or zipped directory

	params:		token
				target			-> ID(s) of requested element(s)
				width (opt)		-> If file is an image
				height (opt)	-> If file is an image

	returns:	file

#### upload
	desc:		uploads file(s)

	params:		token
				target			-> ID of directory to upload to
				file array

	returns:	null

#### public
	desc:		grants access to public share

	params:		hash			-> share hash of public element
				key (opt)

	returns:	token

#### audioinfo
	desc:		returns info for audio file

	params:		token
				target			-> File ID

	returns:	artist and title

#### saveodf
	desc:		saves content to odf file

	params:		token
				target			-> File ID
				data			-> content

	returns:	null

#### savetext
	desc:		saves content to text file

	params:		token
				target			-> File ID
				data			-> content

	returns:	null

#### loadtext
	desc:		loads content from text file

	params:		token
				target			-> File ID

	returns:	text file content

#### sync
	desc:		used for the windows sync client

	params:		token
				target			-> ID of directory to sync
				source			-> list of client files
				lastsync		-> timestamp of last sync

	returns:	list of files to upload | download | delete

### scan
	desc:		scans the filesystem and synchronizes cache-db

	params:		token
				target			-> Directory ID

	returns:	null

## System
All settings regarding the entire server;

#### clearlog
	desc:		Deletes system log from database
				Requires admin

	params:		token

	returns:	null

#### getplugin
	desc:		Downloads plugin-zip from server and extracts it to plugins/
				Requires admin

	params:		token
				plugin name

	returns:	null or error

#### log
	desc:		Returns system log from database
				Requires admin

	params:		token

	returns:	log

#### removeplugin
	desc:		Deletes plugin folder
				Requires admin

	params:		token
				plugin name

	returns:	null or error

#### status
	desc:		Returns server info
				Requires admin

	params:		token

	returns:	Various server infos

#### version
	desc:		Returns current and recent version of simpledrive-server

	params:		Auth token

	returns:	Client version
				Most recent version (if internet available)

### uploadlimit
	desc:		Sets max upload limit

	param:		Auth token
				value			-> upload-limit

	returns:	null

### usessl
	desc:		Enables/disables Force-SSL

	param:		Auth token
				enabled			-> whether or not to force SSL

	returns:	null

### domain
	desc:		Sets server domain (used for public-share-link)

	param:		Auth token
				domain			-> domain name

	returns:	null

## Backup
Uploads (encrypted) files to Google Drive

#### status
	desc:		Returns status of the backup connection

	params:		token

	returns:	enabled and running status

#### token
	desc:		Sets the auth token provided by google

	params:		token
				code			-> google auth token

	returns:	null

#### enable
	desc:		Returns an authentification url for google drive

	params:		token
				pass (opt)		-> password to encrypt files
				enc				-> true if encryption requested

	returns:	Auth url

#### start
	desc:		Starts backup

	params:		token

	returns:	null

#### cancel
	desc:		Cancels backup by removing lock file

	params:		token

	returns: 	null

#### disable
	desc:		Disables backup by deleting token file

	params:		token

	returns:	null

## Users
All settings regarding one ore more user(s)

#### get
	desc:		Returns user

	params:		Auth token
				user			-> username

	returns:	user

#### getall
	desc:		Returns all users
				Requires admin

	params:		token

	returns:	List of all users

#### create
	desc:		Creates a user
				Requires admin

	params:		token
				user			-> username of to be created user
				pass
				mode			-> accepted values: admin | user

	returns:	null or error

#### delete
	desc:		Deletes user
				Requires admin

	params:		token
				user

	returns:	null or error

### quota
	desc:		Returns max, used and free quota

	params:		Auth token
				user			-> username

	returns:	array of max, used and free quota

#### changepw
	desc:		Changes password for user

	params:		Auth token
				currpass		-> current password
				newpass			-> new password

	returns:	null

#### cleartemp
	desc:		Removes user's .tmp-folder

	params:		token

	returns:	null

#### gettheme
	desc:		Returns color and fileview

	params:		auth token

	returns:	array of color and fileview

#### admin
	desc:		Returns user's admin status

	params:		auth token

	returns:	admin status

#### setquota
	desc:		Sets user's max quota
				Requires admin

	params:		auth token

	returns:	null

#### setadmin
	desc:		Changes user's admin status
				Requires admin

	params:		auth token

	returns:	null

#### setautoscan
	desc:		Enables/disables autoscan

	params:		auth token

	returns:	null

#### setfileview
	desc:		Sets fileview

	params:		auth token

	returns:	null

#### setcolor
	desc:		Sets theme-color

	params:		auth token

	returns:	null