# The Bulldog Tutoring Portal
## Premise
The Bulldog Tutoring Portal can best be described as the infrastructure of a comprehensive tutoring management system. While the end goal of the application is to provide an accurate and comprehensive list of all tutors - both associated and unassociated with formal tutoring bodies at TSU - the core responsibilities of the program involve the various management activities associated with the creation and upkeep of such a database.

## Environment
The application is open-source and we encourage any and everyone interested in implementing a similar system for their University to look around and see how we approached this challenge. For those who just want to see the finished result, visit our live site at https://BulldogTutoringPortal.com - keep in mind that a new account creation will limit you to that of a student view, and you will be unable to interact with the bulk of the functionality that is reserved for privileged administration accounts. 

## Programs and Dependencies
There are many moving pieces involved in the use and access of our program on a personal device if you wish to view the application in a development setting. We will provide a basic installation description for those unfamiliar with all of these pieces, but feel free to proceed as you would like.  
  
Tech:   
-Local Web Server (Apache Recommended)  
-MySQL Database Environment (PHPMyAdmin recommended)  
-PHP (8.0+ Recommended)  
-JavaScript  
-HTML/CSS  
  
Dependencies:  
-PHP Composer (2.7.2 Recommended)  
-PHPMailer (Through Composer)  
-SimpleXLSX (Through Composer)

## Recommended Installation Steps
The easiest way to begin the process is to download a web development stack onto your local environment. We recommended MAMP, as this is the stack that each member of our group worked with throughout the process.

### Step 1 - MAMP Installation
Navigate to https://www.mamp.info/en/downloads/ and begin downloading/installing the MAMP version specific to your platform. You will not need MAMP Pro. No special installation information is necessary - MAMP should be downloaded to the location C:\MAMP by default.

### Step 2 - Configure MAMP
Navigate to the location MAMP is downloaded and launch the application. The MAMP dashboard (The initial small initial interface that displays server status) allows for further configuration using the dropdown menus at the top of the screen, including specification of the web server (we tested ours using Apache), PHP version (8.0+ recommended), and port information. This would be a good time to familiarize yourself with the MAMP dashboard, and to ensure that these values, while active by default, are accurate.

### Step 3 - Setting up the Environment
The MAMP web development stack is configured to run files from the folder MAMP/htdocs - naturally, this is where the application needs to be. In the command line, navigate to the htdocs folder within your MAMP parent folder. By default this will be  
  
$ cd C:\MAMP\htdocs  
  
but you may have specified an alternate location upon installation. Next, WHILE IN THE HTDOCS REPOSITORY in the CLI, run the command  
  
$ git clone https://github.com/harrisonhughes/Bulldog-Tutoring-Portal.git  
  
in order to download the application itself to the correct location. By now, you should have a MAMP web development stack on your personal device, and the application files should be present in the htdocs folder of your MAMP environment. 

### Step 4 - Connect the Application to MAMP
Now, in the MAMP dashboard (again, the small initial interface that displays server status), click on the MAMP heading at the top of the page, and select 'Preferences'. You should now be given the option to provide 'My favorite link', which must be selected from the htdocs folder. In this text box, write in  
  
Bulldog-Tutoring-Portal/index.php  
  
thus pointing the stack directly at the application you just downloaded. Be sure to select 'OK'. You should return to the original MAMP dashboard, and select 'Start Servers' to proceed to the final step. 
  
Note: you will need both the 'Apache Server' and 'MySQL Server' to turn green in order to proceed, but the 'Cloud' indicator need not be active. 

### Step 5 - Ensure Access to the Application from MAMP
The final step is to configure the database to allow the program to have its legs. Click the 'Open Webstart Page' button the MAMP Dashboard, which will take you to your local server if the web and database servers were active. If, at the top navigation bar, there is no 'My Favorite Link' tab, it may be best to close the initial MAMP dashboard, and retry the process of adding the 'favorite link' entry to the corresponding tab in 'Preferences' before beginning the process of starting servers and opening the webstart page.  
  
If this still does not provide you with the option to click on your 'Favorite Link', ensure that you have correctly cloned the git repository in the htdocs folder. Once the 'My Favorite Link' pops up, you are officially able to access the application from your local server, and you are one step away from completing the setup.

### Step 6 - Configure MySQL through PHPMyAdmin
Even though the application is active on your device, you must setup the corresponding database to allow the functionalities of the website to work. Navigate back to the MAMP Webstart page on your browser (this was the previous page where you found the 'My Favorite Link' tab) and navigate to 'Tools' and down to 'PHPMyAdmin' to proceed. This is the management page for the application database - you must retrieve the 'commands.txt' database creation file in order to enter the designated database creation instructions into this page.  
  
The file 'commands.txt' can be copied directly from this github repository, or it can be opened and copied from the cloned repository on your personal device, which will be housed in the htdocs folder. Once this is copied, navigate the to 'SQL' tab at the top of the PHPMyAdmin page, and paste the ENTIRETY of the file into the large text box that pops up on the screen. Afterwards, press 'GO' in the bottom right; you have now created the local database linked with the application.  
  
Feel free to manage and test the application as much as you would like using this database; the most important duty in this regard would be to create an account directly on the local application (through the 'My Favorite Link' tab), and change the 'account_type' integer value of that account to '3' in this database management page. This allows your account to have administrative access once you log out and log back in with that same account. Either way, navigate back to your 'Favorite Link', and begin experimenting with the Bulldog Tutoring Portal! 


## Download Extended Features
The previous installation guide will allow access and use of the vast majority of the functionality of the application; however, there are a few special features that require additional installation and configuration if you wish to use them. Specifically, both the mailing and excel sheet file reading functions in the 'newSemester.php' require PHP extensions to execute, and are both managed by the PHP dependency manager 'Composer'. While these functions are not necessary for the majority of the application to operate, the following steps will help you install Composer and the required dependencies.  

Keep in mind that the mailing function of our project has been stripped of all identifying information in order to prevent external use of our application's email account. You can test the mailing function live on our deployed site, https://BulldogTutoringPortal.com by creating an account and navigating back to the 'Forgot Password?' link in the original login page to send yourself a password reset email. If you would like to test the mailing function on your own device, you will need to enter in your desired SMTP host, your email username and password, and the sender's email address and title. These fields are all highlighted in the mailing function in functions.php. Please be responsible when testing and interacting with programmable emailing, and do be sure that you are complying with all anti-spam and relevant privacy laws. 

### Step 1 - Download Composer
As mentioned, Composer is a dependency manager for the programming language PHP. Therefore, these additional installation steps may only be done after you have downloaded MAMP, or have some other instance of PHP installed on your device.  

Navigate to https://getcomposer.org/download/ to choose your desired route to install Composer on your device. We recommend using the installer at the top of the page that provides a link for 'Composer-Setup.exe'. This should be a fairly painless process, as the installer manages most of the configuration including setting up the PATH variable. We used the most recent version of Composer in our testing, which is 2.7.2. so it would be best to select the same version.

### Step 2 - Use Composer to Download Dependencies
In our git repository we have included the file 'composer.json' which delineates the necessary dependencies for the extended functionalities. All you need to do is navigate to the project folder from when you cloned the repository, and 'install' the dependencies. If your MAMP is in the default location, the commands will be  

$ cd C:\MAMP\htdocs\Bulldog-Tutoring-Portal  
$ composer install 

This will create a 'vendor' folder in your root project directory that contains all of the extensions needed from the dependencies.  

### Troubleshooting

We recommend testing the functions and monitoring the error logs found in 'MAMP\logs\php_error.log' to troubleshoot the specific issues based on your installation. A common issue in our testing was specific to the SimpleXLSX extension that handles the Excel files when using MAMP. A fix for this is described below. 

Navigate to the location of the PHP configuration file for the PHP version that you are using. MAMP installs several versions by default, so be sure to navigate to the exact PHP version you are using, or no changes will be made. The PHP version can be seen in the original MAMP dashboard where you launch the full MAMP webpage - navigate to 'Preferences' and over to PHP to check the current version.

The PHP configuration file (using PHP version 8.01 as an example) can be found through the path 'C:\MAMP\conf\php8.0.1' if you installed MAMP in the default location. Open this file in a text editor, and scroll down to the string of blocks that describes several extensions. For example, this could look like  

extension=php_bz2.dll
extension=php_gd.dll
;extension=php_gd2.dll

Now, you need to add the following line in order to fix the issue with file reading.  

extension=php_fileinfo.dll

Be sure that this configuration file is correct for your current PHP version, and be sure to save the configuration file. Stop all of your servers in the MAMP dashboard, close out of the page, and start up again to check if this has solved your problem. 
