import ConfigService from './../components/core/configService';
import defaultConfig from './config';
import pym from 'pym.js';
import merge from 'lodash.merge';
class Bootstrap {
    app;
    configService;
    ready;

    constructor(userConfig) {
        const configuration = merge({}, defaultConfig, userConfig);


        this.configService = new ConfigService(configuration);

        var pymParent = new pym.Parent('phraseanet-embed-frame', this.configService.get('recordUrl'));
        pymParent.iframe.setAttribute('allowfullscreen', '');
        return this;
    }
}

const bootstrap = (userConfig) => {
    return new Bootstrap(userConfig);
};

export default bootstrap;
