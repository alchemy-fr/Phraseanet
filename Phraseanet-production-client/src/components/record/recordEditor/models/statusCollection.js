import merge from 'lodash.merge';
class StatusCollection {
    status;

    constructor(statusList) {
        this.status = statusList;
    }

    getStatus() {
        return this.status;
    }

    fillWithRecordValues(records) {
        let prefilledStatus = {};
        for (let statusIndex in this.status) {
            prefilledStatus[statusIndex] = merge({}, this.status[statusIndex]);
            prefilledStatus[statusIndex]._value = '-1';			// val unknown
            for (let i in records) {
                if (!records[i]._selected) {
                    continue;
                }
                if (records[i].statbits.length === 0) {
                    continue;
                }

                if (prefilledStatus[statusIndex]._value === '-1') {
                    prefilledStatus[statusIndex]._value = records[i].statbits[statusIndex].value;
                } else if (prefilledStatus[statusIndex]._value !== records[i].statbits[statusIndex].value) {
                    prefilledStatus[statusIndex]._value = '2';
                }
            }
        }

        return prefilledStatus;
    }
}
export default StatusCollection;
