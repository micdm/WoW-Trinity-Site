$(document).ready(function() {
	/**
	 * Добавляет счетчик TBE.
	 */
	function AddTbeCounter()
	{
		$("#footer").append(
			'<div id="tbe_counter"><a href="http://tbe.tom.ru"><img src="http://tbe.tom.ru/?a=wow.tomsk.net&c=5&s=8831" alt="TBE" title="place hosts/hits users_online"></a></div>'
		);
	}
	
	/**
	 * Добавляет счетчик Яндекса
	 */
	function AddYandexCounter()
	{
		$("#footer").append(
			'<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript"></script>' +
			'<script type="text/javascript">' +
				'try { var yaCounter694072 = new Ya.Metrika(694072); } catch(e){}' +
			'</script>' +
			'<noscript><img src="//mc.yandex.ru/watch/694072" style="position:absolute" alt=""></noscript>'
		);
	}
	
	/**
	 * Добавляет баннер Беткорта.
	 */
	function AddBetcourtBanner()
	{
		swfobject.embedSWF('/s/flash/betcourt.swf', 'betcourt_banner', 160, 300, '9.0', '/s/js/swfobject/expressInstall.swf');
	}

	AddTbeCounter();
	AddYandexCounter();
	AddBetcourtBanner();
});
