jQuery(function ($) {

    let $codeFilter = $('#bookly-filter-code'),
        $serviceFilter = $('#bookly-filter-service'),
        $staffFilter = $('#bookly-filter-staff'),
        $customerFilter = $('#bookly-filter-customer'),
        $onlyActiveFilter = $('#bookly-filter-active'),
        $couponsList = $('#bookly-coupons-list'),
        $checkAllButton = $('#bookly-check-all'),
        $couponModal = $('#bookly-coupon-modal'),
        $seriesNewTitle = $('#bookly-new-coupon-series-title'),
        $couponNewTitle = $('#bookly-new-coupon-title'),
        $couponEditTitle = $('#bookly-edit-coupon-title'),
        $couponCode = $('#bookly-coupon-code'),
        $generateCode = $('#bookly-generate-code'),
        $seriesMask = $('#bookly-coupon-series-mask'),
        $seriesAmount = $('#bookly-coupon-series-amount'),
        $couponDiscount = $('#bookly-coupon-discount'),
        $couponDeduction = $('#bookly-coupon-deduction'),
        $couponUsageLimit = $('#bookly-coupon-usage-limit'),
        $couponOncePerCst = $('#once_per_customer'),
        $couponDateStart = $('#bookly-coupon-date-limit-start'),
        $clearDateStart = $('#bookly-clear-date-limit-start'),
        $couponDateEnd = $('#bookly-coupon-date-limit-end'),
        $clearDateEnd = $('#bookly-clear-date-limit-end'),
        $couponMinApps = $('#bookly-coupon-min-appointments'),
        $couponMaxApps = $('#bookly-coupon-max-appointments'),
        $couponCustomers = $('#bookly-coupon-customers'),
        $customersList = $('#bookly-customers-list'),
        $couponServices = $('#bookly-js-coupon-services'),
        $couponProviders = $('#bookly-js-coupon-providers'),
        $saveButton = $('#bookly-coupon-save', $couponModal),
        $addButton = $('#bookly-add'),
        $addSeriesButton = $('#bookly-add-series'),
        $deleteButton = $('#bookly-delete'),
        $createAnother = $('#bookly-create-another-coupon'),
        $exportDialog = $('#bookly-export-coupon-dialog'),
        $exportSelectAll = $('#bookly-js-export-select-all', $exportDialog),
        columns = [],
        order = [],
        edit_and_duplicate =
            $('<div class="d-inline-flex">').append(
                $('<button type="button" class="btn btn-default mr-1" data-action="edit"></button>').append($('<i class="far fa-fw fa-edit mr-lg-1" />'), '<span class="d-none d-lg-inline">' + BooklyCouponL10n.edit + '…</span>'),
                $('<button type="button" class="btn btn-default" data-action="edit" data-mode="duplicate"></button>').append($('<i class="far fa-fw fa-clone mr-lg-1" />'), '<span class="d-none d-lg-inline">' + BooklyCouponL10n.duplicate + '…</span>')
            ).get(0).outerHTML,
        row,
        series,
        duplicate
    ;

    $('.bookly-js-select').val(null);
    $.each(BooklyCouponL10n.datatables.coupons.settings.filter, function (field, value) {
        if (value != '') {
            let $elem = $('#bookly-filter-' + field);
            if ($elem.is(':checkbox')) {
                $elem.prop('checked', value == '1');
            } else {
                $elem.val(value);
            }
        }
        // check if select has correct values
        if ($('#bookly-filter-' + field).prop('type') == 'select-one') {
            if ($('#bookly-filter-' + field + ' option[value="' + value + '"]').length == 0) {
                $('#bookly-filter-' + field).val(null);
            }
        }
    });

    /**
     * Init filters.
     */
    function onChangeFilter() {
        dt.ajax.reload();
    }
    $('.bookly-js-select').on('change', onChangeFilter)
    .booklySelect2({
        width: '100%',
        theme: 'bootstrap4',
        dropdownParent: '#bookly-tbs',
        allowClear: true,
        placeholder: '',
        language: {
            noResults: function () {
                return BooklyCouponL10n.noResultFound;
            },
            removeAllItems: function () {
                return BooklyCouponL10n.remove;
            }
        },
        matcher: function (params, data) {
            const term = $.trim(params.term).toLowerCase();
            if (term === '' || data.text.toLowerCase().indexOf(term) !== -1) {
                return data;
            }

            let result = null;
            const search = $(data.element).data('search');
            search &&
            search.find(function (text) {
                if (result === null && text.toLowerCase().indexOf(term) !== -1) {
                    result = data;
                }
            });

            return result;
        }
    });

    $('.bookly-js-select-ajax')
    .val(null)
    .on('change', onChangeFilter)
    .booklySelect2({
        width: '100%',
        theme: 'bootstrap4',
        dropdownParent: '#bookly-tbs',
        allowClear: true,
        placeholder: '',
        language: {
            noResults: function () {
                return BooklyCouponL10n.noResultFound;
            },
            searching: function () {
                return BooklyCouponL10n.searching;
            }
        },
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                params.page = params.page || 1;
                return {
                    action: this.action === undefined ? $(this).data('ajax--action') : this.action,
                    filter: params.term,
                    page: params.page,
                    csrf_token: BooklyL10nGlobal.csrf_token
                };
            }
        },
    });
    $onlyActiveFilter.on('change', onChangeFilter);
    $codeFilter.on('keyup', onChangeFilter);

    /**
     * Init Columns.
     */
    $.each(BooklyCouponL10n.datatables.coupons.settings.columns, function (column, show) {
        if (show) {
            switch (column) {
                case 'services':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            if (data === null) {
                                return BooklyCouponL10n.services.nothingSelected;
                            } else if (data == 1) {
                                return $.fn.dataTable.render.text().display(BooklyCouponL10n.services.collection[row.service_id].title);
                            } else {
                                if (data == BooklyCouponL10n.services.count) {
                                    return BooklyCouponL10n.services.allSelected;
                                } else {
                                    return data + '/' + BooklyCouponL10n.services.count;
                                }
                            }
                        }
                    });
                    break;
                case 'staff':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            if (data === null) {
                                return BooklyCouponL10n.staff.nothingSelected;
                            } else if (data == 1) {
                                if (typeof BooklyCouponL10n.staff.collection[row.staff_id] === 'undefined') {
                                    return BooklyCouponL10n.staff.nothingSelected;
                                } else {
                                    return $.fn.dataTable.render.text().display(BooklyCouponL10n.staff.collection[row.staff_id].title);
                                }
                            } else {
                                if (data == BooklyCouponL10n.staff.count) {
                                    return BooklyCouponL10n.staff.allSelected;
                                } else {
                                    return data + '/' + BooklyCouponL10n.staff.count;
                                }
                            }
                        }
                    });
                    break;
                case 'customers':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            if (data === null) {
                                return BooklyCouponL10n.customers.nothingSelected;
                            } else if (data == 1) {
                                return $.fn.dataTable.render.text().display(row.full_name);
                            } else {
                                if (data == BooklyCouponL10n.customers.count) {
                                    return BooklyCouponL10n.customers.allSelected;
                                } else {
                                    return data + '/' + BooklyCouponL10n.customers.count;
                                }
                            }
                        }
                    });
                    break;
                case 'date_limit_end':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            return row.date_limit_end_formatted;
                        }
                    });
                    break;
                case 'date_limit_start':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            return row.date_limit_start_formatted;
                        }
                    });
                    break;
                default:
                    columns.push({data: column, render: $.fn.dataTable.render.text()});
                    break;
            }
        }
    });

    columns.push({
        data: null,
        responsivePriority: 1,
        orderable: false,
        width: 180,
        render: function (data, type, row, meta) {
            return edit_and_duplicate;
        }
    });

    columns.push({
        data: null,
        responsivePriority: 1,
        orderable: false,
        render: function (data, type, row, meta) {
            return '<div class="custom-control custom-checkbox">' +
                '<input value="' + row.id + '" id="bookly-dt-' + row.id + '" type="checkbox" class="custom-control-input">' +
                '<label for="bookly-dt-' + row.id + '" class="custom-control-label"></label>' +
                '</div>';
        }
    });

    columns[0].responsivePriority = 0;

    $.each(BooklyCouponL10n.datatables.coupons.settings.order, function (_, value) {
        const index = columns.findIndex(function (c) { return c.data === value.column; });
        if (index !== -1) {
            order.push([index, value.order]);
        }
    });

    /**
     * Init DataTables.
     */
    var dt = $couponsList.DataTable({
        order: order,
        info: false,
        searching: false,
        lengthChange: false,
        pageLength: 25,
        pagingType: 'numbers',
        processing: true,
        responsive: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function (d) {
                return $.extend({action: 'bookly_coupons_get_coupons', csrf_token: BooklyL10nGlobal.csrf_token}, {
                    filter: {
                        code: $codeFilter.val(),
                        service: $serviceFilter.val(),
                        staff: $staffFilter.val(),
                        customer: $customerFilter.val(),
                        active: $onlyActiveFilter.prop('checked') ? 1 : 0
                    }
                }, d);
            }
        },
        columns: columns,
        language: {
            zeroRecords: BooklyCouponL10n.zeroRecords,
            processing: BooklyCouponL10n.processing,
            emptyTable: BooklyCouponL10n.emptyTable,
            loadingRecords: BooklyCouponL10n.loadingRecords
        },
        layout: {
            bottomStart: 'paging',
            bottomEnd: null
        }
    });

    /**
     * Select all coupons.
     */
    $checkAllButton.on('change', function () {
        $couponsList.find('tbody input:checkbox').prop('checked', this.checked);
    });

    $couponsList
    // On coupon select.
    .on('change', 'tbody input:checkbox', function () {
        $checkAllButton.prop('checked', $couponsList.find('tbody input:not(:checked)').length == 0);
    })
    // Edit coupon
    .on('click', '[data-action=edit]', function () {
        row = dt.row($(this).closest('td'));
        series = false;
        duplicate = $(this).data('mode') === 'duplicate';
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_coupons_get_coupon_lists',
                csrf_token: BooklyL10nGlobal.csrf_token,
                coupon_id: row.data().id,
                remote: BooklyCouponL10n.customers.remote ? '1' : '0'
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $couponServices.booklyDropdown('setSelected', response.data.service_id);
                    $couponProviders.booklyDropdown('setSelected', response.data.staff_id);
                    if (BooklyCouponL10n.customers.remote) {
                        $couponCustomers.html('');
                        response.data.customers.forEach(function (customer) {
                            $couponCustomers[0].appendChild(new Option(customer.text, customer.id));
                        });
                    }
                    $couponCustomers.val(response.data.customer_id).trigger('change');
                }
                $couponModal.booklyModal('show');
            }
        });
    });

    /**
     * New coupon.
     */
    $addButton.on('click', function () {
        series = false;
        duplicate = false;
        $couponModal.booklyModal('show');
    });

    /**
     * New coupon series.
     */
    $addSeriesButton.on('click', function () {
        series = true;
        duplicate = false;
        $couponModal.booklyModal('show');
    });

    /**
     * On show modal.
     */
    $couponModal
    .on('show.bs.modal', function () {
        if (row) {
            let coupon_data = row.data();
            $couponCode.val(coupon_data.code);
            $couponDiscount.val(coupon_data.discount);
            $couponDeduction.val(coupon_data.deduction);
            $couponUsageLimit.val(coupon_data.usage_limit);
            $couponOncePerCst.val(coupon_data.once_per_customer);
            $couponDateStart.val(coupon_data.date_limit_start !== null ? moment(coupon_data.date_limit_start, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
            $couponDateStart.next('input:hidden').val(coupon_data.date_limit_start);
            $couponDateEnd.val(coupon_data.date_limit_end !== null ? moment(coupon_data.date_limit_end, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
            $couponDateEnd.next('input:hidden').val(coupon_data.date_limit_end);
            $couponMinApps.val(coupon_data.min_appointments);
            $couponMaxApps.val(coupon_data.max_appointments);
            $seriesNewTitle.hide();
            if (duplicate) {
                $couponEditTitle.hide();
                $couponNewTitle.show();
            } else {
                $couponEditTitle.show();
                $couponNewTitle.hide();
            }
        } else {
            $couponCode.val('');
            $seriesMask.val(BooklyCouponL10n.defaultCodeMask);
            $seriesAmount.val(1);
            $couponDiscount.val('0');
            $couponDeduction.val('0');
            $couponUsageLimit.val('1');
            $couponOncePerCst.val('0');
            $couponDateStart.val('');
            $couponDateEnd.val('');
            $couponMinApps.val('1');
            $couponMaxApps.val('');
            $couponCustomers.val(null).trigger('change');
            $couponEditTitle.hide();
            if (series) {
                $couponNewTitle.hide();
                $seriesNewTitle.show();
            } else {
                $couponNewTitle.show();
                $seriesNewTitle.hide();
            }
            $couponServices.booklyDropdown('selectAll');
            $couponProviders.booklyDropdown('selectAll');
        }
        $('.bookly-js-series-field').toggle(series);
        $('.bookly-js-coupon-field').toggle(!series);
        $couponCode.trigger('change');
        $createAnother.prop('checked', false);
    })
    .on('hidden.bs.modal', function () {
        row = null;
        $('[name=date_limit_start]', $couponModal).val('');
        $('[name=date_limit_end]', $couponModal).val('');
    });

    /**
     * Code.
     */
    $couponCode.on('keyup change', function () {
        $generateCode.prop('disabled', $couponCode.val().length && $couponCode.val().indexOf('*') === -1);
    });

    /**
     * Generate code.
     */
    $generateCode.on('click', function () {
        let ladda = Ladda.create(this);
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_coupons_generate_code',
                csrf_token: BooklyL10nGlobal.csrf_token,
                mask: $couponCode.val()
            },
            dataType: 'json',
            success: function (response) {
                ladda.stop();
                if (response.success) {
                    $couponCode.val(response.data.code);
                    $generateCode.prop('disabled', true);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    /**
     * Date limit start.
     */
    $couponDateStart.daterangepicker({
        parentEl: '#bookly-coupon-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function (start) {
        $couponDateStart.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateStart.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $couponDateStart.on('apply.daterangepicker', function (ev, picker) {
        $couponDateStart.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateStart.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateStart.on('click', function () {
        $couponDateStart.val('');
        $couponDateStart.next('input:hidden').val(null);
    });

    /**
     * Date limit end.
     */
    $couponDateEnd.daterangepicker({
        parentEl: '#bookly-coupon-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function (start) {
        $couponDateEnd.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateEnd.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $couponDateEnd.on('apply.daterangepicker', function (ev, picker) {
        $couponDateEnd.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateEnd.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateEnd.on('click', function () {
        $couponDateEnd.val('');
        $couponDateEnd.next('input:hidden').val(null);
    });

    /**
     * Customers list.
     */
    if (BooklyCouponL10n.customers.remote) {
        $couponCustomers.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function () {
                    return BooklyCouponL10n.noResultFound;
                },
                searching: function () {
                    return BooklyCouponL10n.searching;
                }
            },
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    params.page = params.page || 1;
                    return {
                        action: 'bookly_get_customers_list',
                        filter: params.term,
                        page: params.page,
                        csrf_token: BooklyL10nGlobal.csrf_token
                    };
                },
                processResults: function (data, params) {
                    var customers = [];
                    params.page = params.page || 1;
                    data.results.forEach(function (customer) {
                        BooklyCouponL10n.customers.collection[customer.id] = customer;
                        customers.push({
                            id: customer.id,
                            text: customer.name
                        });
                    });
                    return {
                        results: customers,
                        pagination: data.pagination
                    };
                }
            },
        });
    } else {
        $couponCustomers.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function () {
                    return BooklyCouponL10n.noResultFound;
                }
            }
        });
    }

    $couponCustomers.on('change', function () {
        $customersList.empty();
        $couponCustomers.find('option:selected').each(function () {
            let $option = $(this),
                $li = $('<li class="form-row align-items-center"/>'),
                $span = $('<span class="col-11 text-truncate"/>'),
                $a = $('<a class="far fa-fw fa-trash-alt text-danger" href="#"/>')
            ;
            $span.text($option.text());
            $a.on('click', function (e) {
                e.preventDefault();
                var newValues = [];
                $.each($couponCustomers.val(), function (i, id) {
                    if (id !== $option.val()) {
                        newValues.push(id);
                    }
                });
                $couponCustomers.val(newValues);
                $couponCustomers.trigger('change');
            });
            $a.attr('title', BooklyCouponL10n.removeCustomer);
            $li.append($span).append($a);
            $customersList.append($li);
        });
    });

    /**
     * Services.
     */
    $couponServices.booklyDropdown();

    /**
     * Providers (staff).
     */
    $couponProviders.booklyDropdown();

    /**
     * Save coupon.
     */
    $saveButton.on('click', function (e) {
        e.preventDefault();
        let data = booklySerialize.form($(this).parents('form')),
            ladda = Ladda.create(this);
        if (row && !duplicate) {
            data.id = row.data().id;
        }
        if (series) {
            data.create_series = 1;
        }
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: booklySerialize.buildRequestData('bookly_coupons_save_coupon', data),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    dt.ajax.reload(null, false);
                    if (!series && $createAnother.prop('checked')) {
                        row = null;
                        $couponNewTitle.show();
                        $couponEditTitle.hide();
                        $couponCode.val('');
                        $createAnother.prop('checked', false);
                    } else {
                        $couponModal.booklyModal('hide');
                    }
                } else {
                    alert(response.data.message);
                }
                ladda.stop();
            }
        });
    });

    /**
     * Delete coupons.
     */
    $deleteButton.on('click', function () {
        if (confirm(BooklyCouponL10n.areYouSure)) {
            let ladda = Ladda.create(this),
                data = [],
                $checkboxes = $couponsList.find('tbody input:checked');
            ladda.start();
            $checkboxes.each(function () {
                data.push(this.value);
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookly_coupons_delete_coupons',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    data: data
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        dt.ajax.reload(null, false);
                    } else {
                        alert(response.data.message);
                    }
                    ladda.stop();
                }
            });
        }
    });

    $exportSelectAll
    .on('click', function () {
        let checked = this.checked;
        $('.bookly-js-columns input', $exportDialog).each(function () {
            $(this).prop('checked', checked);
        });
    });

    $('.bookly-js-columns input', $exportDialog)
    .on('change', function () {
        $exportSelectAll.prop('checked', $('.bookly-js-columns input:checked', $exportDialog).length == $('.bookly-js-columns input', $exportDialog).length);
    });
});