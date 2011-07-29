$(document).ready(function() {
	$('th.active').click(function() {
		Sort($(this));
	});

	var isDesc = true;
	var column = 0;

	/**
	 * Сравнивает два ряда.
	 */
	function Compare(a, b)
	{
		if (a.weight > b.weight)
		{
			return isDesc ? -1 : 1;
		}
		else if (a.weight < b.weight)
		{
			return isDesc ? 1 : -1;
		}
		else
		{
			if (a.content > b.content)
			{
				return isDesc ? -1 : 1;
			}
			else if (a.content < b.content)
			{
				return isDesc ? 1 : -1;
			}
			else
			{
				return 0;
			}
		}
	}
	
	/**
	 * Находит позицию сортируемой колонки.
	 */
	function GetColumnPosition(table)
	{
		var pos = 0;
		table.find('th').each(function(i) {
			if ($(this).hasClass('current_sort'))
			{
				pos = i;
				return false;
			}
		});

		return pos;
	}
	
	/**
	 * Возвращает номер месяца.
	 */
	function GetMonth(long)
	{
		var months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
		for (var i in months)
		{
			if (long == months[i])
			{
				return i;
			}
		}
		
		return 0;
	}
	
	/**
	 * Выдает порядок сортировки для даты.
	 * Варианты:
	 * 10 секунд назад
	 * 10 минут назад
	 * сегодня, 10:10
	 * вчера, 10:10
	 * 10 октября, 10:10
	 * 10 октября 2010, 10:10
	 */
	function ParseDate(content)
	{
		var weight = 2;
		var date = new Date();
		
		var matches = null;
		if (matches = content.match(/^(\d{1,2}) сек/))
		{
			date.setTime(date - matches[1] * 1000);
		}
		else if (matches = content.match(/^(\d{1,2}) мин/))
		{
			date.setTime(date - matches[1] * 1000 * 60);
		}
		else if (matches = content.match(/^сегодня, (\d{1,2}):(\d{1,2})$/))
		{
			date.setHours(matches[1]);
			date.setMinutes(matches[2]);
		}
		else if (matches = content.match(/^вчера, (\d{1,2}):(\d{1,2})$/))
		{
			date.setTime(date - 1000 * 3600 * 24);
			date.setHours(matches[1]);
			date.setMinutes(matches[2]);
		}
		else if (matches = content.match(/^(\d{1,2}) ([^ ]+), (\d{1,2}):(\d{1,2})$/))
		{
			date.setMonth(GetMonth(matches[2]), matches[1]);
			date.setHours(matches[3], matches[4]);
		}
		else if (matches = content.match(/^(\d{1,2}) ([^ ]+) (\d{4}), (\d{1,2}):(\d{1,2})$/))
		{
			date.setFullYear(matches[3], GetMonth(matches[2]), matches[1]);
			date.setHours(matches[4], matches[5]);
		}
		else
		{
			weight = null;
		}
		
		if (weight)
		{
			return {
				weight: weight,
				content: date.getTime()
			};
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Собирает в кучу ряды и по пути расставляет им веса.
	 */
	function GetRows(table, pos)
	{
		var rows = [];
		table.find('tr:gt(0)')
			.each(function() {
				var row = $(this);

				var weight = 0;
				var content = row.find('td:eq(' + column + ')').text();
				
				var data = null;
				if (content.search(/^\d+(\.\d+)?$/) != -1)
				{
					//Числа:
					weight = 0;
					content = Number(content);
				}
				else if (content.search(/^[\w]+$/i) != -1)
				{
					//Нормальные строки:
					weight = 1;
				}
				else if (data = ParseDate(content))
				{
					weight = data.weight;
					content = data.content;
				}
				else if (isNaN(parseFloat(content)) == false)
				{
					//Строки, начинающиеся числами:
					weight = 3;
					content = parseFloat(content);
				}
				else
				{
					//Все остальное:
					weight = 4;
				}
				
				row.weight = weight;
				row.content = content;
				rows.push(row);
				
				row.find('td:eq(' + pos + ')').addClass('current_sort');
			})
			.remove();
		
		return rows;
	}
	
	/**
	 * Перестраивает таблицу с новым порядком рядов.
	 */
	function RebuildTable(table, rows)
	{
		//Перестраиваем таблицу заново:
		var needRecount = table.find('tr:eq(0) th:eq(0)').text() == '#';
		for (var i = 0; i < rows.length; i += 1)
		{
			var row = rows[i];
			
			//Зебра:
			row.removeClass('light dark');
			row.addClass((i % 2) ? 'dark' : 'light');

			//Первая колонка:
			if (needRecount)
			{
				row.find('td:eq(0)').text(i + 1);
			}
			
			table.append(row);
		}
	}
	
	/**
	 * Самая главная функция.
	 */
	function Sort(th)
	{
		var table = th.parent().parent();

		//Не сортируем таблицы с одной строкой, которая растянута на все столбцы (то есть фактически таблица пустая):
		if (table.find('tr:eq(1) td:first').attr('colspan') != 1)
		{
			return;
		}
		
		//Убираем у колонок признак сортировки и добавляем его целевой колонке:
		table.find('.current_sort')
			.removeClass('current_sort sort_order_asc sort_order_desc')
			.find('img')
				.remove();

		th.addClass('current_sort');
		
		//Находим позицию колонки:
		var pos = GetColumnPosition(table);
		
		//Определяем порядок сортировки:
		isDesc = (pos == column) ? !isDesc : true;
		column = pos;
		
		//Собираем все ряды в массив:
		var rows = GetRows(table, pos);
		
		//Сортируем:
		rows.sort(Compare);
		
		//Перестраиваем таблицу:
		RebuildTable(table, rows);
		
		//Подсвечиваем заголовок отсортированной колонки:
		th.addClass('sort_order_' + (isDesc ? 'desc' : 'asc'));
	}
});
