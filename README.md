# PipePS



## An extensible & customizable minimalistic CMS with onsite content editor

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
Once installed this example of default.html in the tpl/ folder **displays a login panel and 3 text-blocks of editable contents** allowing to edit onsite once logged. For more info check the 9-step how-to section at the bottom.

You can also template any other format like json, xml or binary data, include your own modules and much more. PipePS has superfast response times (usually under 10ms), both headless and html modes, an agnostic template processor that can be used either on html, xml, json and many more formats. Mode is customizable by setting configuration variables before including pipeps.min.php: for example with $GLOBALS['pipeps-mode'] you can switch modes; it has a fully programmable life-cycle via actions list: with the **programmable main/default sequence at the top-most part configuration section of pipeps.min.php** (it runs a list of modules same as your own: init, checkbefore, etcfind, etcrun, i18n, doactions, tplfind, include and checkafter). Default sequence results in loading and rendering the site/url corresponding {{...}} template, default.html.

Get it at **<a href="https://github.com/iagoFG/PipePS/blob/main/pipeps.min.php">pipeps.min.php</a>**, install on your server and check HOW-TOs: you can easily create new editable sections by naming them with new tags, placing an {{editable ...}} block that will be replaced with its contents or, if you are logged in with a edit button that allows to change texts onsite:

![image](https://github.com/user-attachments/assets/36cfbf98-78a8-4a39-9fff-4b4c744a259c)

And as you click it an edit box will popup:

![image](https://github.com/user-attachments/assets/1033a3bc-cd98-495d-9e7b-5090a3930c36)

PipePS will load and store all data resources including templates, editable sections and other contents using **an agnostic virtual I/O system managed with $handleset callbacks that any module can extend to use/implement any kind of storage type** (default mode is as plain files but you can override it and store on databases, on remote distributed filesystems, or in your own defined service).

## HOW-TO manual setup (step-by-step)
You can use the basic prebuilt example site which is provided in the folder example/ and login with user test and pass test **(remember to change it)** ...or... if you need/want to setup manually the environment, you can follow these instructions:
1. **copy <a href="https://github.com/iagoFG/PipePS/blob/main/pipeps.min.php">pipeps.min.php</a>** into webserver, create index.php with: <?php include("../path/to/pipeps.min.php");
2. **open the index.php** with your browser: should see a blank page: pipeps.log WILL BE CREATED somewhere.
3. **locate pipeps.log**: it should be on /var/log or /tmp or C:\Windows\Temp or maybe in sys_get_temp_dir() folder ...or near index.php (this lastone alternative is NOT RECOMMENDED and you should AVOID it)
4. **create your site folder**: localhost/ or domain.com/ or 12.34.56.78/ (whatever used in browser, check log)
5. inside place etc/ folder, empty index.html, a **config named like AB12-CD34-EF45.php** with your own named-code
6. next to etc/ folder another **folder named usr-AB12-CD34-EF45/** (use same own code used for config file)
7. inside usr-.../ **create another folder named tpl/** with the **example default.html file** in the intro
8. **reload your browser**, you should see now the interpreted html template instead of a blank page
9. next to tpl/ **create the empty folders edit/ mod/ sessions/ and users/ and then create inside a YOUR_USER_NAME.user** file replacing values, with the following contents: &user=YOUR_USER_NAME&hash=PASSWORD_MD5_HASH&perm.edit=1&

## HOW-TO make your own modules
1. if not exists, create mod/ folder inside usr-.../
2. create a php file named mod_YOURMODNAME.php (replace YOURMODNAME with whatever you want) and write this test code:
```php
     <?php
       pout("[hello world from mod_YOURMODNAME]");
```
3. open the template where you want to include a call to the module, for example default.html and write {{YOURMODNAME}} wherever you want to place the call.
4. open the browser and go to the page showing the template you modified in the previous step.
