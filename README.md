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
1. copy pipeps.min.php in your webserver, ideally OUT OF THE www public folder, and create a index.php file in the www public folder including an <?php include("../path/to/pipeps.min.php"); pointing the path of pipeps.min.php.
2. access from the browser to index.php, you should get a white empty page, but a pipeps.log file will be created reporting found problems (PipePS does not log errors to stdout except extreme cases).
3. to proceed further you need to locate the pipeps.log: usually by default PipePS will create the file (or append data) on one of the following locations: /var/log or /tmp or C:\Windows\Temp or PHP sys_get_temp_dir() folder or --only if last resort-- in the same folder ./ of index.php (but this last option is severely NOT recomended). IF you couldn't locate pipeps.log or if it was created in the wrong place try to make one of the other locations available by creating an empty file and giving permissions to it or define a new no-public-accesible location in $GLOBALS['pipeps-config'] > log-path at pipeps.min.php beginning.
4. following step is creating a site folder next to index.php (or in other location if you change pipeps.min.php configuration at the file top). You should name it localhost/ or if your domain.com/
5. inside the site folder create an etc/ folder with both a configuration php file named like LONG-CODE-TYPE-NAME.php (for example AB12-CD34-EF45-GH67.php ...that name will be used as the internal site id for other folders/files). You must also create an empty or root-redirecting index.html for security reasons.
6. next to etc/ folder create a folder named usr-AB12-CD34-EF45-GH67/ and inside create a folder named tpl/ with a default.html template file.
7. next to the tpl/ folder create an edit/ folder, a sessions/ folder and an users/ folder.
