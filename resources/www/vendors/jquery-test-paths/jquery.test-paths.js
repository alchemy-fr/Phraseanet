(function ($) {

    var methods = {
        init: function (options) {
            var settings = {
                'url': '/admin/test-paths/'
            };
            return this.each(function () {

                var $this = $(this), data = $(this).data('path_file_tests');

                if (!data) {
                    if (options) {
                        $.extend(settings, options);
                    }
                    $this.data('path_file_tests', {});

                    $this.after('<img class="loader" style="visibility:hidden;" src="/assets/common/images/icons/loader-black.gif"/>');
                    $this.after('<img class="reload" src="/assets/common/images/icons/reload.png"/>');
                    $this.after('<img class="status btn-img" src="/assets/common/images/icons/delete.png" width="16"/>');


                    $this.bind('keyup blur', function () {
                        var el_loader = $this.nextAll('.loader');
                        var el_status = $this.nextAll('.status');

                        var tests = [];

                        if ($this.data('ajax_path_test') && typeof $this.data('ajax_path_test').abort == 'function')
                            $this.data('ajax_path_test').abort();

                        if (!$this.hasClass('test_executable') && !$this.hasClass('test_writeable') && !$this.hasClass('test_readable'))
                            return;


                        if (!$this.hasClass('required') && $.trim($this.val()) === '') {
                            el_status.css('visibility', 'hidden');
                            return;
                        }

                        if ($this.hasClass('test_executable')) {
                            tests.push('executable');
                        }
                        if ($this.hasClass('test_writeable')) {
                            tests.push('writeable');
                        }
                        if ($this.hasClass('test_readable')) {
                            tests.push('readable');
                        }

                        var ajax = $.ajax({
                            dataType: 'json',
                            type: "GET",
                            url: settings.url,
                            data: {
                                path: $this.val(),
                                tests: tests
                            },
                            beforeSend: function () {
                                el_loader.css('visibility', 'visible');
                            },
                            success: function (data) {
                                el_loader.css('visibility', 'hidden');
                                if ($this.hasClass('required')) {
                                    $this.addClass('field_error');
                                }
                                if ($this.hasClass('test_executable') && (data.executable === false || data.file !== true)) {
                                    el_status.attr('src', '/assets/common/images/icons/delete.png').css('visibility', 'visible');
                                    return;
                                }
                                if ($this.hasClass('test_writeable') && data.results === false) {
                                    el_status.attr('src', '/assets/common/images/icons/delete.png').css('visibility', 'visible');
                                    return;
                                }
                                if ($this.hasClass('test_readable') && data.results === false) {
                                    el_status.attr('src', '/assets/common/images/icons/delete.png').css('visibility', 'visible');
                                    return;
                                }
                                el_status.attr('src', '/assets/common/images/icons/ok.png').css('visibility', 'visible');
                                $this.removeClass('field_error');
                                return;
                            },
                            timeout: function () {
                                el_loader.css('visibility', 'hidden');
                                el_status.attr('src', '/assets/common/images/icons/delete.png').css('visibility', 'visible');
                            },
                            error: function () {
                                el_loader.css('visibility', 'hidden');
                                el_status.attr('src', '/assets/common/images/icons/delete.png').css('visibility', 'visible');
                            }
                        });
                        $this.data('ajax_path_test', ajax);
                    });

                    $this.trigger('keyup');

                    $this.nextAll('.reload').bind('click', function () {
                        $this.trigger('keyup');
                    });
                }
            });
        },
        destroy: function () {
            return this.each(function () {
                $(this).data('path_file_tests', null);
            });
        }
    };

    $.fn.path_file_test = function (method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.path_file_test');
        }
    };
})(jQuery);


(function ($) {

    var methods = {
        init: function (options) {
            var settings = {
                'url': '/admin/tests/pathurl/url/'
            };
            return this.each(function () {

                var $this = $(this), data = $(this).data('url_tests');

                if (!data) {
                    if (options) {
                        $.extend(settings, options);
                    }
                    $this.data('url_tests', {});

                    $this.after('<img class="loader" style="visibility:hidden;" src="/assets/common/images/icons/loader-black.gif"/>');
                    $this.after('<img class="reload" src="/assets/common/images/icons/reload.png"/>');
                    $this.after('<img class="status" src="/assets/common/images/icons/delete.png"/>');

                    $this.bind('keyup blur', function () {
                        var el_loader = $(this).nextAll('.loader');
                        var el_status = $(this).nextAll('.status');

                        var listable = $this.hasClass('listable');
                        var required = $this.hasClass('required');
                        var same_domain = $this.hasClass('same_domain');

                        var value = $.trim($this.val());

                        if (!required && value === '') {
                            el_status.attr('src', '/assets/common/images/icons/ok.png');
                            return;
                        }
                        if (required && value === '') {
                            el_status.attr('src', '/assets/common/images/icons/delete.png');
                            return;
                        }
                        if (same_domain && value.substring(0, 1) != '/') {
                            value = '/' + value;
                        }
                        if (same_domain) {
                            value = location.protocol + '//' + location.hostname + value;
                        }

                        if ($this.data('ajax_url_test') && typeof $this.data('ajax_url_test').abort == 'function')
                            $this.data('ajax_url_test').abort();

                        var ajax = $.ajax({
                            type: "GET",
                            url: settings.url,
                            dataType: 'json',
                            data: {
                                url: value
                            },
                            beforeSend: function () {
                                el_loader.css('visibility', 'visible');
                            },
                            success: function (datas) {
                                el_loader.css('visibility', 'hidden');
                                if (datas.code === 404) {
                                    el_status.attr('src', '/assets/common/images/icons/delete.png');
                                    return;
                                }
                                if (!listable && datas.code === 403) {
                                    el_status.attr('src', '/assets/common/images/icons/ok.png');
                                }
                                else {
                                    el_status.attr('src', '/assets/common/images/icons/delete.png');
                                }
                                return;
                            },
                            timeout: function () {
                                el_loader.css('visibility', 'hidden');
                                el_status.attr('src', '/assets/common/images/icons/delete.png');
                            },
                            error: function (datas) {
                                el_loader.css('visibility', 'hidden');
                                el_status.attr('src', '/assets/common/images/icons/delete.png');
                            }
                        });
                        $this.data('ajax_url_test', ajax);
                    });

                    $this.trigger('keyup');
                    $this.nextAll('.reload').bind('click', function () {
                        $this.trigger('keyup');
                    });
                }
            });
        },
        destroy: function () {
            return this.each(function () {
                $(this).data('url_tests', null);
            });
        }
    };

    $.fn.url_test = function (method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.url_test');
        }
    };
})(jQuery);


