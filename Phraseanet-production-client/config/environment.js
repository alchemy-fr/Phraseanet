/* eslint-disable */
import dotenv from 'dotenv';
import path from 'path';
import {
    defaults
}
from 'lodash';

dotenv.load({
    silent: false,
    path: path.resolve(__dirname, '../.env')
});

const {
    ENVIRONMENT,
    HOT_RELOAD,
    NODE_ENV,
    HOST,
    PORT,
    WEBPACK_DEV_SERVER_PORT
} = defaults(process.env, {
        ENVIRONMENT: 'development',
        // localhost
        HOST: 'localhost',
        // port to use with the asset server
		PORT: '8000'
    }),
    IS_PROD = () => {
        return ( NODE_ENV === 'production' || ENVIRONMENT === 'prod' || ENVIRONMENT === 'production' );
    },
    IS_DEV = !IS_PROD,
    ENVIRONMENT_NAME = IS_PROD ? 'production' : 'development';

process.env.NODE_ENV = IS_PROD ? 'production' : 'development';

export default {
    IS_PROD,
    IS_DEV,
    ENVIRONMENT_NAME,
    HOST,
    PORT,
    WEBPACK_DEV_SERVER_PORT,
    HOT_RELOAD
};
/* eslint-enable */