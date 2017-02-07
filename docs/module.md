Module configure
---
### Property

* `uploadFolder` the directory where stored file. Default is `@app/web/uploads/images`. If folder not existed, roxy will auto-create it. New feature 'AddUserIdToPath',if you use AddUserIdToPath then `[userid]` must be add to string
* `uploadUrl` url of uploadFolder not include 'http://domain.com' must be start with / . if you use AddUserIdToPath then `[userid]` must be add to string. Default is `/uploads/images`
* `onlyAutorizeUsers` access only autorized users. Default `true`
* `AddUserIdToPath` Added user id to path extend on onlyAutorizeUsers. Add `[userid]` to path uploadFolder and uploadUrl. Default is `true`
* `defaultView` display type (`thumb`  or `list`). Default is `thumb` 
* `dateFormat` Datetime format. Default is `Y-m-d H:i`. See: http://php.net/manual/en/function.date.php
* `rememberLastFolder` would you want to remember last folder? Default is `true`
* `rememberLastOrder` would you want to remember last order? Default is `true`
* `allowExtension` allowed files extension. Default is `jpeg jpg png gif mov mp3 mp4 avi wmv flv mpeg webm`
* `NoAlias`  No alias `@` in path uploadFolder . Default is `true`
* `NoChangeFileExt` Do not change the file extension from. Default is `true`
* `NoFooterButton` No show buttons on Footer (Insert and Close). Default is `false`

### Example
Add to config file:
```
	'modules' => [
		'roxymce' => [
			'class' => 'bogdik\roxymce\Module',
			'uploadFolder' => 'uploads/[userid]/media',
			'uploadUrl' => '/uploads/[userid]/media',
		],
	],
```
