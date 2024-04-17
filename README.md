# The Bulldog Tutoring Portal
## Premise
The Bulldog Tutoring Portal can best be described as the infrastructure of a comprehensive tutoring management system. While the end goal of the application is to provide an accurate and comprehensive list of all tutors - both associated and unassociated with formal tutoring bodies at TSU - the core responsibilities of the program involve the various management activities associated with the creation and upkeep of such a database.

## Environment
The application is open-source and we encourage any and everyone interested in implementing a similar system for their University to look around and see how we approached this challenge. For those who just want to see the finished result, visit our live site at https://BulldogTutoringPortal.com - keep in mind that a new account creation will limit you to that of a student view, and you will be unable to interact with the bulk of the functionality that is reserved for privileged administration accounts. 

## Programs and Dependencies
There are many moving pieces involved in the use and access of our program on a personal device if you wish to view the application in a development setting. We will provide a basic installation description for those unfamiliar with all of these pieces, but feel free to proceed as you would like.
Programs and Dependencies Used:\n 
-Local Web Server (Apache Recommended)\n
-MySQL Database Environment (PHPMyAdmin recommended)\n
-PHP (8.0+ Recommended)\n
-JavaScript\n
-HTML/CSS\n
-PHP Composer (2.7.2 Recommended)\n
-PHPMailer (Through Composer)\n
-SimpleXLSX (Through Composer)

## Recommended Installation Steps
The easiest way to begin the process is to download a web development stack onto your local environment. We recommended MAMP, as this is the stack that each member of our group worked with throughout the process.

### Step 1 - MAMP Installation
Navigate to https://www.mamp.info/en/downloads/ and begin downloading/installing the MAMP version specific to your platform. You will not need MAMP Pro. No special installation information is necessary - MAMP should be downloaded to the location C:\MAMP by default.

### Step 2 - Configure MAMP
Navigate to the location MAMP is downloaded, and launch the application. The MAMP dashboard (The initial small initial interface that displays server status) allows for further configuration using the bars at the top of the screen, including specification of the web server (we tested ours using Apache), PHP version (8.0+ recommended), and port information. 

### Step 3 - Setting up the Environment
The MAMP web development stack is configured to run files from the folder MAMP/htdocs - naturally, this is where the application needs to be. In the command line, navigate to the htdocs folder within your MAMP parent folder. By default this will be\n 
cd C:\MAMP\htdocs\n
but you may have specified an alternate location upon installation. Next, WHILE IN THE HTDOCS FOLDER, run the command\n
git clone https://github.com/harrisonhughes/Bulldog-Tutoring-Portal.git\n
in the command line in order to download the application itself. By now, you should have a MAMP web development stack on your personal device, and the application files should be present in the htdocs folder of your MAMP environment. 

### Step 4 - Connect the Application to MAMP
Now, in the MAMP dashboard (again, the small initial interface that displays server status), click on the MAMP heading at the top of the page, and select 'Preferences'. You shoul dnow be given the option to provide your 'My favorite link', which must be selected from the htdocs folder. In this text box, write in\n 
Bulldog-Tutoring-Portal/index.php\n
thus pointing the stack directly at the application you just downloaded. Be sure to select 'OK'. You should return to the original MAMP dashboard, and select 'Start Servers' to proceed to the final step.\n
Note: you will need both the 'Apache Server' and 'MySQL Server' to turn green in order to proceed, but the 'Cloud' indicator need not be active. 

### Step 5 - Ensure Access to the Application from MAMP
The final step is to configure the database to allow the program to have its legs. Click the 'Open Webstart Page' button the MAMP Dashboard, which will take you to your local server if the web and database servers were active. If, at the top navigation bar, there is no 'My Favorite Link' tab, it may be best to close the initial MAMP dashboard, and retry the process of adding the 'favorite link' entry to the corresponding tab in 'Preferences' before beginning the process of starting servers and opening the webstart page.\n\n
If this still does not provide you with the option to click on your 'Favorite Link', ensure that you have correctly cloned the git repository in the htdocs folder. Once the 'My Favorite Link' pops up, you are officially able to access the application from your local server, and you are one step away from completing the setup.

### Step 6 - Configure MySQL through PHPMyAdmin
Even though the applicaiton is active on your device, you must setup the corresponding database to allow the funcitonalities of the website to work. Navigate back to the MAMP Webstart page (this was the one you found the 'My Favorite Link' tab) and navigate to 'Tools', and down to 'PHPMyAdmin' to proceed. This is the management page for the application database - you must find the 'commands.txt' database creation file in order to enter the designated database creation instructions into this page.\n\n
The file 'commands.txt' can be copied directly from this github repository, or it can be opened and copied from the cloned repository on your personal device, which will be housed in the htdocs folder. Once this is copied, navigate the to 'SQL' tab at the top of the PHPMyAdmin page, and paste the ENTIRETY of the file into the large text box that pops up on the screen. Afterwards, press 'GO' in the bottom right; you have now created the local database linked with the application.\n\n
Feel free to manage and test the application as much as you would like using this database; the most important duty in this regard would be to create an account directly on the local application (through the 'My Favorite Link' tab), and change the 'account_type' integer value of that account to '3' in this database management page. This allows your account to have administrative access once you log out and log back in with that same account. Either way, navigate back to your 'Favorite Link', and begin experimenting with the Bulldog Tutoring Portal! 
