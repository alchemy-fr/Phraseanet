import ApplicationConfigService from './applicationConfigService';

let instance = null;

class ConfigService extends ApplicationConfigService {
    constructor(configuration) {
        super(configuration);
        if (!instance) {

            instance = this;
        }

        return instance;
    }
}

export default ConfigService;
