// vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
(function($, undefined) {

	$.nette.ext('dataGrid', {
		load: function() {
			$('table.datagrid a.datagrid-ajax').off('click').on('click', this.fnA);
			$('table.datagrid td.checker input:checkbox').off('click').on('click', this.fnCbClicked);
			$('table.datagrid tbody tr td:not(.checker, .actions)').off('click').on('click', this.fnLineChecker);
			$('table.datagrid tr.header th.checker span.invert').off('click').on('click', this.fnInvertorClick);
			$('form.datagrid tr.footer input[name=page]').off('keypress').on('keypress', this.fnPageChange);
			$('input:checkbox, select', 'form.datagrid tr.filters').off('change').on('change', this.fnSelectFilter);
			$('form.datagrid tr.filters input[type=text]').off('keypress').on('keypress', this.submitTextFilter);
			$('form.datagrid tr.footer select[name=items]').off('change').on('change', this.fnLinesChange);
			$('form.datagrid tr.footer a.loadMore').off('click').on('click', this.fnLoadMore);

			$('form.datagrid table.datagrid tr.footer input[name=pageSubmit]').hide();
			$('form.datagrid table.datagrid tr.footer input[name=itemsSubmit]').hide();

			$('input.datepicker:not([readonly])').datepicker({dateFormat: 'yy-mm-dd'});

			$('table.datagrid td.checker input:checkbox').each(this.fnCbClicked);
			}
		}, {
		fnA: function() {
			$.nette.ajax({
				url: this.href
				});
			return false;
			},
		// coloring of checked row
		fnCbClicked: function() {
			var tr=$(this).parentsUntil('table', 'tr');
			if ($(this).is(':checked')) {
				tr.addClass('selected');
				}
			else {
				tr.removeClass('selected');
				}
			},
		fnLineChecker: function(e) {
			// only clicks by left button
			if (e.button!==0) {
				return true;
				}

			var row=$(this).parent('tr');

			// multiple rows by holding down SHIFT or CTRL
			if ((e.shiftKey || e.ctrlKey) && previous) {
				var current=$(this).parents('table.datagrid').find('tr').index($(this).parent('tr')); // index to
				if (previous>current) {
					var tmp=current;
					current=previous;
					previous=tmp;
					}
				current++;
				row=$(this).parents('table.datagrid').find('tr').slice(previous, current);
				}
			else {
				previous=$(this).parents('table.datagrid').find('tr').index($(this).parent('tr'));
				}

			var $cb=$('td.checker input:checkbox', row);
			// highlighting of row(s)
			if (row.hasClass('selected')) {
				row.removeClass('selected');
				$cb.prop('checked', false);
				}
			else {
				if ($cb.is(':checkbox')) {
					row.addClass('selected');
					$cb.prop('checked', true);
					}
				}
			return false;
			},
		fnInvertorClick: function() {
			var table=$(this).parents('table.datagrid');
			var selected=table.find('tr.selected');
			var unselected=table.find('tbody tr').filter(':not(.selected)');

			selected.removeClass('selected');
			selected.find('td.checker input:checkbox').prop('checked', false);
			unselected.addClass('selected');
			unselected.find('td.checker input:checkbox').prop('checked', true);
			},
		fnPageChange: function(e) {
			if (e.keyCode===13) {
				$(this).parents('form.datagrid').find('input:submit[name=pageSubmit]').click();
				return false;
				}
			},
		fnLinesChange: function() {
			return $(this).parents('form.datagrid').find('input:submit[name=itemsSubmit]').click();
			},
		fnSelectFilter: function() {
			$(this).parents('form.datagrid').find('input:submit[name=filterSubmit]').click();
			return false;
			},
		submitTextFilter: function(e) {
			if (e.keyCode===13) {
				$(this).parents('form.datagrid').find('input:submit[name=filterSubmit]').click();
				return false;
				}
			},
		fnLoadMore: function() {
			var data=$(this).data();
			data.id=$(this).parents('form.datagrid')[0].id;
			$.post(
				this.href,
				data,
				function(data) {
					if (data.more) {
						var $grid=$('form.datagrid#'+data.more.grid),
							$more=$grid.find('.loadMore');
						$grid.find('tbody tr').last().after(data.more.rows);
						if (data.more.more) {
							$more.data('gridMore', data.more.more);
							}
						else {
							$more.hide();
							}
						return false;
						}
					}
				);
			return false;
			},
		// index from
		previous: null
		});

	})(jQuery);
