<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
	<!--
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
	<META HTTP-EQUIV="Pragma-directive" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-Directive" CONTENT="no-cache">
	<META HTTP-EQUIV="Expires" CONTENT="0">
	-->

  <title>{$settings->get('helpdesk_title')}</title>
  <link rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}" type="image/x-icon">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/assets/skins/sam/container.css?v={$smarty.const.APP_BUILD}{/devblocks_url}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/assets/skins/sam/autocomplete.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/tabview/assets/skins/sam/tabview.css?v={$smarty.const.APP_BUILD}{/devblocks_url}" />  
  <link type="text/css" rel="stylesheet" href="{devblocks_url}styles/default/cerberus.css?v={$smarty.const.APP_BUILD}{/devblocks_url}">

  <script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/yahoo-dom-event/yahoo-dom-event.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>  
  <script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/connection/connection-debug.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/animation/animation-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>    
  <script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/autocomplete-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/utilities/utilities.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/container-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/tabview/tabview-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 

  <script language="javascript" type="text/javascript">{php}DevblocksPlatform::printJavascriptLibrary();{/php}</script>
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/display.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/config.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/kb.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  
</head>

<body class="yui-skin-sam">
