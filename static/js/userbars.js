$(document).ready(function() {
	function GenerateCode()
	{
		//Настройки юзербара:
		var flags = 0;
		for (var i in values)
		{
			if (values[i].is(":checked"))
			{
				flags |= options[i];
			}
		}
		
		//Итоговая ссылка на картинку:
		var code = "[img]" + USERBARS_PAGE + guid.val() + "/" + (flags ? (flags + "/") : "") + "[/img]";
		
		//Проверяем, нужна ли реферальная ссылка:
		if (needRef.is(":checked"))
		{
			code = "[url=" + REGISTRATION_PAGE + "?ref=" + guid.val() + "]" + code + "[/url]";
		}
		
		//Код:
		$("#userbar_code").html(code);
		
		var html = code
			.replace(/\[img\](.+)\[\/img\]/ , "<img src='$1'>")
			.replace(/\[url=([^\]]+)\]/ , "<a href='$1'>")
			.replace(/<br \/>/g , "")
			.replace("[/url]" , "</a>");
		
		//HTML-код:
		$("#userbar_code_plain").text(html);
		
		//Предпросмотр:
		$("#userbar_preview").html(html);
	}
	
	//Идентификатор персонажа:
	var guid = $("#userbar_settings select");
	
	//Нужна ли реферальная ссылка:
	var needRef = $("#userbar_settings [name='referral']");
	
	//Остальные опции-флажки:
	var options = {
		"gray": 0x01,
		"gm": 0x02,
		"online": 0x04,
		"logout": 0x08
	};
	
	var values = {};
	for (var i in options)
	{
		values[i] = $("#userbar_settings [name='" + i + "']");
	}
	
	$("#userbar_settings").find("select,input").click(GenerateCode);
	GenerateCode();
});
