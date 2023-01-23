let instance = null;
import * as _ from 'underscore';

export default class ApplicationConfigService {
    configuration;

    constructor(config) {
        // if( !instance ) {
        //     instance = this;
        // }
        this.configuration = config;
        // return instance;
    }

    get(configKey) {
        if (configKey !== undefined) {
            var foundValue = this._findKeyValue(configKey || this.configuration);
            switch (typeof foundValue) {
                case 'string':
                    return foundValue;
                default:
                    return foundValue ? foundValue : null;

            }

        }

        return this.configuration;
    }

    set(configKey, value) {
        if (configKey !== undefined) {
            if (typeof this.configuration[configKey] === 'object') {
                // merge
                this.configuration[configKey] = _.extend({}, this.configuration[configKey], value);
            } else {
                this.configuration[configKey] = value;
            }
        }
    }

    // @TODO cast
    _findKeyValue(configName) {
        if (!configName) {
            return undefined;
        }

        let isStr = _.isString(configName);
        let name = isStr ? configName : configName.name;
        let path = configName.indexOf('.') > 0 ? true : false;

        if (path) {
            return this._search(this.configuration, name);

        }
        var state = this.configuration[name];
        if (state && (isStr || (!isStr && state === configName))) {
            return state;
        } else if (isStr) {
            return state;
        }
        return undefined;
    }

    // @TODO cast
    _search(obj, path) {
        if (_.isNumber(path)) {
            path = [path];
        }
        if (_.isEmpty(path)) {
            return obj;
        }
        if (_.isEmpty(obj)) {
            return null;
        }
        if (_.isString(path)) {
            return this._search(obj, path.split('.'));
        }

        var currentPath = path[0];

        if (path.length === 1) {
            if (obj[currentPath] === void 0) {
                return null;
            }
            return obj[currentPath];
        }

        return this._search(obj[currentPath], path.slice(1));
    }
}
