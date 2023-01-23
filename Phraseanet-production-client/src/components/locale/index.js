import i18next from 'i18next';
import Backend from 'i18next-xhr-backend';
import $ from 'jquery';
import * as Rx from 'rx';

let instance = null;
class LocaleService {
    constructor(options) {
        if (!options) {
            options = {};
        }

        // can be instanciated only once
        if (!instance) {
            instance = this;
        }

        if (options.locale === undefined) {
            options.locale = $('html').attr('lang') || 'fr';
        }
        this.locale = options.locale;
        this.isCached = false;
        this.cachedTranslations = {};
        this.configService = options.configService;
        this.path = this.configService.get('translations');
        return instance;
    }

    t(key) {
        if (this.isCached && this.translate !== undefined) {
            return this.translate(key);
        } else {
            throw new Error('locale not loaded');
        }
    }

    getLocale() {
        return this.locale;
    }

    getTranslations() {
        return this.cachedTranslations;
    }

    fetchTranslations(data) {
        data = data || {};
        let i18nPromise = $.Deferred();
        i18next
            .use(Backend)
            .init({
                lng: this.locale,
                backend: {
                    loadPath: this.path,

                }
            }, (err, t) => {
                this.isCached = true;
                this.translate = t;
                this.cachedTranslations = i18next.getResourceBundle(this.locale);
                i18nPromise.resolve(instance);
                if (data.callback !== undefined) {
                    data.callback();
                }
                //resolve(this.i18n);
            });
        // });
        this.i18n = i18nPromise.promise();
        this.stream = Rx.Observable.fromPromise(this.i18n);
        return this.i18n;
    }
}

export default LocaleService;
