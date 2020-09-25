/* global expect */
/* eslint padded-blocks: 0*/
/* eslint no-unused-expressions: 0*/
/* eslint max-nested-callbacks: 0*/
import chai from 'chai';
const expect = chai.expect;

import ProductionApplication from '../src';

describe('ProductionApplication Unit test', () => {

    it('should be an object', () => {
        expect(typeof ProductionApplication).to.eql('object');
    });

    it('should expose a public API', () => {
        const methods = Object.keys(ProductionApplication);
        expect(methods.length).to.eql(2);
        expect(methods).to.contain('bootstrap');
        expect(methods).to.contain('utils');
    });

});
