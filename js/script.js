/**
 * ce code est amicalement sponsorisé par la méthode rache
 */
;(function() {
	/* IE8+, le reste n'a pas de JS, on peut donc utiliser tranquille querySelector	*/
	var $ = function(selector, el) {
		if (!el) {el = document;}
		return el.querySelector(selector);
	};
	var $$ = function(selector, el) {
		if (!el) {el = document;}
		return Array.prototype.slice.call(el.querySelectorAll(selector));
	};
	var clean = function(str){
		return str.replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
	};
	var hasClass = function(el, className) {
		return clean(el.className).indexOf(className) > -1;
	};
	var addClass = function(el, className){
		if (!className) return;
		if (el.classList){
			el.classList.add(className);
			return;
		}
		if (hasClass(el, className)) return;
		el.className = clean(el.className + ' ' + className);
	};
	var removeClass = function(el, className){
		if (!className) return;
		if (el.classList){
			el.classList.remove(className);
			return;
		}
		el.className = el.className.replace(new RegExp('(^|\\s)' + className + '(?:\\s|$)'), '$1');
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
	var H = window.history && typeof history.pushState !== 'undefined';

	var currentHash = $('#item').getAttribute('data-hash') !== null ? $('#item').getAttribute('data-hash') : '',
		nextHash = '',
		nextContent = '',
		dummy = document.createElement('div'),
		preloadItem = function() {
			microAjax($('a.reader-choice.sad').href + "?isajax=1", function(res) {
				if (res.status == 200) {
					nextContent = dummy.innerHTML = res.responseText;
					nextHash = $('#item[data-hash]', dummy) !== null ? $('#item[data-hash]', dummy).getAttribute('data-hash') : '';
				}
			});
		},
		updateItemView = function(event, url) {
			var itemContainer = $('#item');
			currentHash = itemContainer.getAttribute('data-hash') !== null ? itemContainer.getAttribute('data-hash') : '';

			itemContainer.innerHTML = '';
			addClass(itemContainer, 'loading');

			if (nextHash !== '' && nextHash !== currentHash) {
				removeClass(itemContainer, 'loading');
				$('#content').innerHTML = nextContent;
				_gaq.push(['_trackEvent', 'choix', 'triste', $('#item .for-analytics').innerHTML]);
				preloadItem();
			} else {
				microAjax(url + "?isajax=1", function(res) {
					removeClass(itemContainer, 'loading');
					if (res.status == 200) {
						$('#content').innerHTML = res.responseText;
						_gaq.push(['_trackEvent', 'choix', 'triste', $('#item .for-analytics').innerHTML]);
					}
					preloadItem();
				});
			}
			if (H) history.pushState({ content: $('#content').innerHTML, url: url }, document.title, url);
			preventDefault(event);
		},
		ajaxifyLink = function(event, url) {
			microAjax(url + "?isajax=1", function(res) {
				if (res.status == 200) {
					$("#content").innerHTML = res.responseText;
					if (H) history.pushState({ content: $('#content').innerHTML, url: url }, document.title, url);
				}
			});
			preventDefault(event);
		},
		/* code trop secret tu peux pas test */
		wat = [38,38,40,40,37,39,37,39,66,65],
		typed = [],
		decode = function(input) {
			var dummy = document.createElement('textarea');
			dummy.innerHTML = input;
			return dummy.value;
		},
		zomthng = function() {
			if (confirm(decode("B&#79;&#85;M &#33; Tu&#32;v&#97;&#115;&#32;&#234;&#116;&#114;&#101; &#114;ed&#105;&#114;&#105;&#103;&#233; &#118;&#101;rs&#32;&#108;e&#32;&#115;&#105;&#116;e de l&#39;au&#116;&#101;&#117;r d&#101;&#32;c&#101;&#32;&#115;&#105;&#116;&#101; s&#117;&#98;l&#105;me&#46;"))) {
				window.location.href = decode("&#104;t&#116;&#112;&#58;&#47;&#47;&#101;mm&#97;nue&#108;&#112;e&#108;l&#101;tier&#46;c&#111;&#109;");
				_gaq.push(['_trackEvent', 'secret', 'valider']);
			} else {
				_gaq.push(['_trackEvent', 'secret', 'annuler']);
			}
		};

	setTimeout(function() { preloadItem(); }, 100);
	addEvent($('#content'), 'click', function(e) {
		var target = e.target || e.srcElement;
		if ( target && target.nodeName === 'A' && hasClass(target, 'reader-choice') && hasClass(target, 'sad') ) {
			updateItemView(e, target.href);
		}

		if ( target && target.nodeName === 'A' && hasClass(target, 'reader-choice') && hasClass(target, 'happy') ) {
			_gaq.push(['_trackEvent', 'choix', 'content']);
			ajaxifyLink(e, target.href);
		}

		if ( target && target.nodeName === 'A' && hasClass(target, 'toggle-view') ) {
			ajaxifyLink(e, target.href);
		}

		if ( target && target.nodeName === 'A' && hasClass(target, 'share-link') && hasClass(target, 'twitter') ) {
			_gaq.push(['_trackEvent', 'partage', 'twitter']);
		}
	});

	var initialContent = $('#content').innerHTML;
	addEvent(window, 'popstate', function (e) {
		var content = (e.state && e.state.content) ? e.state.content : initialContent;
		$('#content').innerHTML = content;
	});

	addEvent(document, 'keydown', function(e) {
		if (typed.length >= wat.length) typed.shift();
		typed.push(e.keyCode);
		if (typed.toString() == wat.toString())
			zomthng();
	});

	addEvent($('#content'), 'keydown', function(e) {
		var target = e.target || e.srcElement;
		if ( target && target.nodeName === 'TEXTAREA' && hasClass(target, 'share-text') )
			$('.share-link.twitter').href = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(target.value);
	});
})();