define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    return function (config) {
        // Cache references to input fields using camelCase
        const baseUrlInput = $('#ls_mag_service_base_url');
        const selectedStoreInput = $('#ls_mag_service_selected_store');
        const licenseValidityLabel = $('#row_ls_mag_service_license_validity .value');
        const centralVersionLabel = $('#row_ls_mag_service_ls_central_version .control-value');
        const tenantInput = $('#ls_mag_service_tenant');
        const clientIdInput = $('#ls_mag_service_client_id');
        const clientSecretInput = $('#ls_mag_service_client_secret');
        const centralTypeInput = $('#ls_mag_service_central_type');
        const companyNameInput = $('#ls_mag_service_company_name');
        const environmentNameInput = $('#ls_mag_service_environment_name');
        const webServiceUri = $('#ls_mag_service_web_service_uri');
        const odataUri = $('#ls_mag_service_odata_service_uri');
        const usernameInput = $('#ls_mag_service_username');
        const passwordInput = $('#ls_mag_service_password');

        // Group all related actions into one object
        const api = {
            // Validates base URL input using Magento's validation library
            validateBaseUrl: function () {
                // Base URL is only used (and visible) for SaaS; on-premise uses the web service URIs
                if (centralTypeInput.val() !== '1') {
                    return true;
                }
                baseUrlInput.validation();
                return baseUrlInput.validation('isValid');
            },

            // Fetches store and hierarchy data via AJAX call to the backend
            fetchStores: function () {
                if (!api.validateBaseUrl()) {
                    return;
                }

                $.ajax({
                    url: config.ajaxUrl,
                    type: 'POST',
                    showLoader: true,
                    dataType: 'json',
                    data: api.collectCommonData(),
                    complete: function (response) {
                        const data = response.responseJSON;

                        // Update store and hierarchy dropdowns
                        api.updateSelect('#ls_mag_service_selected_store', data.store);
                        api.updateSelect('#ls_mag_service_replicate_hierarchy_code', data.hierarchy);

                        // Update version and license display
                        centralVersionLabel.html(data.version || '');
                        licenseValidityLabel.html(data.licenseHtml || '');
                        let isValidObject = typeof data.pong === 'object'
                        // Show success/failure message
                        const message = isValidObject
                            ? Object.entries(data.pong).map(([k, v]) => `${k}: ${v}`).join('\n')
                            : data.pong;
                        alert(message);
                    }
                });
            },

            // Fetches hierarchy data for the selected store
            fetchHierarchy: function () {
                if (!api.validateBaseUrl()) {
                    return;
                }

                $.ajax({
                    url: config.hierarchyUrl,
                    type: 'POST',
                    showLoader: true,
                    dataType: 'json',
                    data: {
                        ...api.collectCommonData(),
                        storeId: selectedStoreInput.val()
                    },
                    complete: function (response) {
                        api.updateSelect('#ls_mag_service_replicate_hierarchy_code', response.responseJSON.hierarchy);
                    }
                });
            },

            // Fetches and populates tender types for each item
            fetchStoreTenderTypes: function () {
                if (!api.validateBaseUrl()) {
                    return;
                }

                $.ajax({
                    url: config.storeTenderTypesUrl,
                    type: 'POST',
                    showLoader: true,
                    dataType: 'json',
                    data: {
                        ...api.collectCommonData(),
                        storeId: selectedStoreInput.val()
                    },
                    complete: function (response) {
                        const types = response.responseJSON.storeTenderTypes;

                        // Loop through item1, item2, item3 tender type fields
                        for (let i = 1; i <= 3; i++) {
                            const select = $(`#item${i}_tender_type`);
                            select.empty();
                            types.forEach(type => {
                                select.append(new Option(type.label, type.value, false, type.selectedKey === `item${i}`));
                            });
                        }
                    }
                });
            },

            // Updates a <select> element with given items
            updateSelect: function (selector, items) {
                const select = $(selector);
                select.empty();
                items.forEach(item => {
                    select.append(new Option(item.label, item.value));
                });
            },

            // Gathers common data used in multiple AJAX requests
            collectCommonData: function () {
                return {
                    baseUrl: baseUrlInput.val(),
                    scopeId: config.websiteId,
                    tenant: tenantInput.val(),
                    client_id: clientIdInput.val(),
                    client_secret: clientSecretInput.val(),
                    central_type: centralTypeInput.val(),
                    company_name: companyNameInput.val(),
                    environment_name: environmentNameInput.val(),
                    web_service_uri: webServiceUri.val(),
                    odata_uri: odataUri.val(),
                    username: usernameInput.val(),
                    password: passwordInput.val()
                };
            }
        };

        // When store dropdown changes, fetch hierarchy and tender types
        selectedStoreInput.on('change', function () {
            if ($(this).val()) {
                api.fetchHierarchy();
                api.fetchStoreTenderTypes();
            }
        });

        // Enabling the module re-enables every field in the group, including those hidden by the
        // central type dependency; re-run that dependency so they go back to disabled/ignored.
        $('#ls_mag_ls_enable_enabled').on('change', function () {
            setTimeout(function () {
                if (centralTypeInput.length) {
                    centralTypeInput[0].dispatchEvent(new Event('change', {bubbles: true}));
                }
            }, 0);
        });

        // When "Validate" button is clicked, fetch store data
        $('#validate_base_url').on('click', api.fetchStores);
    };
});
