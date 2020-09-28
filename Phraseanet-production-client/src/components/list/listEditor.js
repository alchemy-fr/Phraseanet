import $ from 'jquery';

const listEditor = (services, options) => {
    const { configService, localeService, appEvents } = services;
    const { $container, listManagerInstance } = options;
    const $editor = $('#list-editor-search-results');
    const $form = $('#ListManager .editor').find('form[name="list-editor-search"]');

    $('a.next, a.prev', $editor).bind('click', function (event) {
        event.preventDefault();
        const page = $(this).attr('value');

        $('input[name="page"]', $form).val(page);
        $form.trigger('submit');
    });

    $('input[name="page"]', $form).val('');

    $('th.sortable', $editor).bind('click', function () {

            let $this = $(this);

            let sort = $('input', $this).val();
            let ord = 'asc';

            if ((sort === $('input[name="srt"]', $form).val()) && ($('input[name="ord"]', $form).val() === 'asc')) {
                ord = 'desc';
            }

            $('input[name="srt"]', $form).val(sort);
            $('input[name="ord"]', $form).val(ord);

            $form.trigger('submit');
        })
        .bind('mouseover', function () {
            $(this).addClass('hover');
        })
        .bind('mouseout', function () {
            $(this).removeClass('hover');
        });

    $('tbody tr', $editor).bind('click', function () {

        let $this = $(this);
        let usr_id = $('input[name="usr_id"]', $this).val();

        let counters = $('#ListManager .counter.current, #ListManager .lists .list.selected .counter');

        if ($this.hasClass('selected')) {
            $this.removeClass('selected');
            listManagerInstance.getList().removeUser(usr_id);

            counters.each(function (i, el) {
                let n = parseInt($(el).text().split(' ')[0], 10);
                if($(el).hasClass('current')) 
                    $(el).text(n - 1 + ' people');
                else 
                    $(el).text(n - 1);
            });
        } else {
            $this.addClass('selected');
            listManagerInstance.getList().addUser(usr_id);

            counters.each(function (i, el) {
                let n = parseInt($(el).text(), 10);

                if($(el).hasClass('current')) 
                    $(el).text(n + 1 + ' people');
                else 
                    $(el).text(n + 1);
            });
        }

    });
};

export default listEditor;
