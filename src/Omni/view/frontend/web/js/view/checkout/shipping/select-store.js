
define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'lsomni/map-loader',
    'lsomni/map',
    'mage/url'
], function (Component, ko, $, $t, modal, quote, MapLoader, map,url) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Ls_Omni/checkout/shipping/select-store'
        },
        isClickAndCollect: ko.observable(false),
        isSelectStoreVisible: ko.observable(false),
        isMapVisible: ko.observable(false),

        initialize: function () {
        	var self = this;
        	quote.shippingMethod.subscribe(function () {
            	if (quote.shippingMethod().carrier_code == 'clickandcollect') {
            		self.isClickAndCollect(true);
                    var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
                    if(stores.totalRecords > 1) {
                        self.isSelectStoreVisible(true);
                    }
            	} else {
            		self.isClickAndCollect(false);
            	}
            });

            this.isMapVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                } else {
                    self.getPopUp().closeModal();
                }
            });

            ko.bindingHandlers.datetimepicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    $el.datetimepicker({
                        'showTimepicker': false,
                        'format': 'yyyy-MM-dd'
                    });
                    var writable = valueAccessor();
                    if (!ko.isObservable(writable)) {
                        var propWriters = allBindingsAccessor()._ko_property_writers;
                        if (propWriters && propWriters.datetimepicker) {
                            writable = propWriters.datetimepicker;
                        } else {
                            return;
                        }
                    }
                    writable($(element).datetimepicker("getDate"));
                },
                update: function (element, valueAccessor) {
                    var widget = $(element).data("DateTimePicker");
                    if (widget) {
                        var date = ko.utils.unwrapObservable(valueAccessor());
                        widget.date(date);
                    }
                }
            };

            $('body').on('click', '.apply-store', function() {
                $('#pickup-store').val($(this).data('id'));
                $('#selected-store-msg')
                    .show()
                    .find('span')
                    .text( $(this).data('name') );
                self.isMapVisible(false);
            });

            $('body').on('click', '.check-store-availability', function() {
                var selectedStore = $(this).data('id');
                var controllerUrl = self.getBaseUrl("omni/stock/store"+"?storeid="+selectedStore);
                var backUrl = self.getBaseUrl("checkout/cart");
                var flag = "1";
                $.ajax({
                    url: controllerUrl,
                    type: 'POST',
                    dataType: "json",
                    beforeSend: function() {
                        $('.custom-loader').append('<p>loading...</p>');
                    },
                    complete: function() {
                        $('.custom-loader').html("");
                    },
                    success: function (data) {
                        $(".stock-remarks ul").html("");
                        $(".stock-remarks strong").html("");
                        for(var i = 0;i < data.stocks.length;i++){
                            if(data.stocks[i].status === "0" && flag === "1"){
                                flag = "0";
                            }
                            if(data.stocks[i].status === "0" ){
                                $(".stock-remarks ul").append("<li><strong>" + data.stocks[i].name + ":</strong> <span style='color:red'>"+ data.stocks[i].display +"</span></li>")
                            }else{
                                $(".stock-remarks ul").append("<li><strong>" + data.stocks[i].name + ":</strong> <span style='color:green'>"+ data.stocks[i].display +"</span></li>")
                            }

                        }
                        if(flag ==="1"){
                            $('.apply-store').removeAttr('disabled');
                        }else{
                            $(".stock-remarks").append("<strong>"+data.remarks+" <a href='"+backUrl+"'>cart</a></strong>");
                        }
                    },
                    error: function(xhr) { // if error occured
                        console.log(xhr.statusText + xhr.responseText);
                    }
                });

            });

            return this._super();
        },

        showMap: function () {
            this.isMapVisible(true);
        },
        getBaseUrl: function(param) {
            return url.build(param);
        },
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                MapLoader.done($.proxy(map.initMap, this)).fail(function() {
                    console.error("ERROR: Google maps library failed to load");
                });
                popUp = modal({
                	'responsive': true,
                	'innerScroll': true,
                    'buttons': [],
                    'type': 'slide',
                    'modalClass': 'mc_cac_map',
                	closed: function() {
            			self.isMapVisible(false)
            		}
                }, $('#map-canvas'));
            }
            return popUp;
        }
    });
});