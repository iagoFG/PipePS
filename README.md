# PipePS

## A flexible minimalistic CMS with onsite content editor

```html
     <!DOCTYPE html>
     <html>
       <body>
         <header class="text-end">{{login panel}}<hr></header>
         <main>
           <section class="col-sm-4">{{editable section_1}}</section>
           <section class="col-sm-4">{{editable section_2}}</section>
           <section class="col-sm-4">{{editable section_3}}</section>
         </main>
       </body>
     </html>
```
Example of default.html in the tpl/ folder. If not other mode like a non automatic or library is specified with $GLOBALS['pipeps-mode'] before including pipeps.min.php, then the actions of the programmable main/default sequence in the topmost configuration section will be executed (init, checkbefore, etcfind, etcrun, i18n, doactions, tplfind, include and checkafter) resulting in rendering the corresponding {{...}} template, for example default.html like shown.

You can name editable sections with any tag, placing an {{editable ...}} tag will be replaced with the section contents or, if you login with a user allowed to edit, an edit tag will appear:

![image](https://github.com/user-attachments/assets/36cfbf98-78a8-4a39-9fff-4b4c744a259c)

And if you click it an edit box will popup:

![image](https://github.com/user-attachments/assets/1033a3bc-cd98-495d-9e7b-5090a3930c36)

By default PipePS will store editable sections contents as html files in the site/usr-CODE/edit/ folder where are loadable by webservices or by the CMS itself.

## Howto manual setup (step-by-step)
1. copy pipeps.min.php in your webserver and create an index.php including it <?php include("../path/to/pipeps.min.php"); (ideally place pipeps.min.php OUT of the www public folder).
2. access from the browser to index.php you should get a blank page AND A PIPEPS.LOG FILE WILL BE CREATED somewhere.
3. locate pipeps.log: usually should be on /var/log or /tmp or C:\Windows\Temp or PHP sys_get_temp_dir() folder or --only if couldn't write elsewhere-- could be in the same folder ./ of index.php (not recommended).
4. create your first site folder, for starting you can create it next to index.php (auto-config) and should be named what you use in your browser: localhost/ or domain.com/ or 12.34.56.78/ (whatever you use, pipeps.min.php should detect it, check in the log).
5. inside the site folder create an etc/ folder with a couple of files: a configuration php file named like LONG-CODE-TYPE-NAME.php (for example AB12-CD34-EF45-GH67.php, but you should write your own) and an empty index.html (for basic security reasons, but is better to place the site folder outside the www public folder).
6. next to etc/ folder create a folder named with the same code you used for the etc config file, for example usr-AB12-CD34-EF45-GH67/
7. inside usr-.../ create another folder named tpl/ with a default.html template file. Reload page on browser, default.html contents should be shown.
8. finally optional: you can create some other folders like an edit/ folder, a sessions/ folder and an users/ folder, next to the tpl/ folder previously created.
