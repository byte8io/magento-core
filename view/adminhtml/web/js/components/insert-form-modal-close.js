/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/components/insert-form'
], function ($, _, Insert) {
    'use strict';

    /**
     * Insert form for modals that submit via ajax.
     *
     * - Shows the server's messages: errors inside the modal (above the form),
     *   success messages on the page once the modal is closed. Without this the
     *   response of a modal form is stored in `responseData` and never rendered,
     *   so a failed request looks like the button did nothing.
     * - Closes the modal named in `formModalProvider` on success.
     *
     * Accepted response shapes: {error: bool, messages: {error: [], success: []}},
     * {error: bool, messages: []} and {error: bool, message: ''}.
     */
    return Insert.extend({
        defaults: {
            messagesClass: 'messages',
            listens: {
                responseData: 'onResponse'
            },
            modules: {
                formModalProvider: '${ $.formModalProvider }'
            }
        },

        /**
         * @param {Object} responseData
         */
        onResponse: function (responseData) {
            if (!responseData || _.isUndefined(responseData.error)) {
                return;
            }

            if (responseData.error) {
                this.showMessages(responseData, true);

                return;
            }

            if (this.formModalProvider && this.formModalProvider()) {
                this.formModalProvider().closeModal();
            }

            this.showMessages(responseData, false);
        },

        /**
         * Render response messages. Errors go inside the modal (scoped by the
         * modal's root selector, `prefix`), successes go to the page.
         *
         * @param {Object} responseData
         * @param {Boolean} isError
         */
        showMessages: function (responseData, isError) {
            var messages = this.collectMessages(responseData),
                scope = isError && this.prefix ? this.prefix : null,
                actions = $('.page-main-actions', scope).first(),
                messagesClass = this.messagesClass;

            if (!messages.length) {
                return;
            }

            $('body').notification('clear');
            _.each(messages, function (message) {
                $('body').notification('add', {
                    error: isError,
                    message: message,

                    /**
                     * @param {String} msg
                     */
                    insertMethod: function (msg) {
                        var $wrapper = $('<div></div>').addClass(messagesClass).html(msg);

                        if (actions.length) {
                            actions.after($wrapper);
                        } else {
                            $('body').prepend($wrapper);
                        }
                    }
                });
            });
        },

        /**
         * @param {Object} responseData
         * @return {Array}
         */
        collectMessages: function (responseData) {
            var raw = responseData.messages || responseData.message || [],
                result = [];

            if (_.isString(raw)) {
                return [raw];
            }

            _.each(raw, function (value) {
                if (_.isString(value)) {
                    result.push(value);
                } else {
                    _.each(value, function (nested) {
                        if (_.isString(nested)) {
                            result.push(nested);
                        }
                    });
                }
            });

            return result;
        }
    });
});
