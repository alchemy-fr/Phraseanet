import * as utils from '../../../src/components/utils/utils';


describe('Utils Component', () => {
    it('is available', () => {
        expect(utils).not.to.be.null;
    });
    describe('escapeHtml method', () => {
        it('is available', () => {
            expect(utils.escapeHtml).not.to.be.null;
        });
        it('transform a simple phrase', () => {
            const simplePhrase = 'hello world';
            expect(utils.escapeHtml(simplePhrase)).to.eql(simplePhrase)
        });
        it('transform html entities', () => {
            const simplePhrase = 'hello & world';
            expect(utils.escapeHtml(simplePhrase)).to.eql('hello &amp; world')
        });
    });
});
