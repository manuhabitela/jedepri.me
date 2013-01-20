<?php 
//VUE A L'ARRAAAAAACHE
foreach ($items as $key => $item): ?>
	<div id="<?php echo $item['id'] ?>">
		<button class="ban" data-id="<?php echo $item['id'] ?>">Bannir cet item</button>
		<?php if (!empty($items[$key+1])): ?>
		<a href="#<?php echo $item['id'] ?>" class="ok">Ok</a>
		<?php endif ?>
		<p><?php echo $item['title'] ?></p>
		<?php if ($item['content-type'] == 'img-url'): ?>
		<img class="item-img" src="<?php echo $item['content'] ?>" alt="<?php echo $item['title'] ?>">	
		<?php endif ?>

		<?php if ($item['content-type'] == 'text'): ?>
		<p class="item-text"><?php echo $item['content'] ?></p>
		<?php endif ?>
	</div>
	<br><br>
<?php endforeach ?>
<style type="text/css">
	.item-img {
		max-width: 500px;
	}
</style>
<script>
var $$ = function(selector, el) {
	if (!el) {el = document;}
	return Array.prototype.slice.call(el.querySelectorAll(selector));
};
var preventDefault = function(event) {
	if (event.preventDefault) event.preventDefault(); else event.returnValue = false;
};
var addEvent = (function () {
	var filter = function(el, type, fn) {
		for ( var i = 0, len = el.length; i < len; i++ ) {
			addEvent(el[i], type, fn);
		}
	};
	if ( document.addEventListener ) {
		return function (el, type, fn) {
			if ( el && el.nodeName || el === window ) {
					el.addEventListener(type, fn, false);
			} else if (el && el.length) {
					filter(el, type, fn);
			}
		};
	}
	return function (el, type, fn) {
		if ( el && el.nodeName || el === window ) {
			el.attachEvent('on' + type, function () { return fn.call(el, window.event); });
		} else if ( el && el.length ) {
			filter(el, type, fn);
		}
	};
})();
var microAjax = function(url,callbackFunction){this.bindFunction=function(caller,object){return function(){return caller.apply(object,[object])}};this.stateChange=function(object){if(this.request.readyState==4)this.callbackFunction(this.request)};this.getRequest=function(){if(window.ActiveXObject)return new ActiveXObject("Microsoft.XMLHTTP");else if(window.XMLHttpRequest)return new XMLHttpRequest;return false};this.postBody=arguments[2]||"";this.callbackFunction=callbackFunction;this.url=url;
this.request=this.getRequest();if(this.request){var req=this.request;req.onreadystatechange=this.bindFunction(this.stateChange,this);if(this.postBody!==""){req.open("POST",url,true);req.setRequestHeader("X-Requested-With","XMLHttpRequest");req.setRequestHeader("Content-type","application/x-www-form-urlencoded");req.setRequestHeader("Connection","close")}else req.open("GET",url,true);req.send(this.postBody)}};



addEvent($$('.ban'), 'click', function(e) {
	preventDefault(e);
	microAjax('/admin/ban/' + e.target.getAttribute('data-id'), function(res) {
		if (res.status == 200) {
			e.target.parentNode.innerHTML = '';
		}
	});
});
</script>