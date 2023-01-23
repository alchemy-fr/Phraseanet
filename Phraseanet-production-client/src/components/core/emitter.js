import * as Rx from 'rx';
var hasOwnProp = {}.hasOwnProperty;

function createName(name) {
    return '$' + name;
}

let Emitter = function () {
    this.subjects = {};
};

Emitter.prototype.emit = function (name, data) {
    var fnName = createName(name);
    this.subjects[fnName] || (this.subjects[fnName] = new Rx.Subject());
    this.subjects[fnName].onNext(data);

    return this.subjects[fnName];
};

Emitter.prototype.listen = function (name, handler) {
    var fnName = createName(name);
    this.subjects[fnName] || (this.subjects[fnName] = new Rx.Subject());
    return this.subjects[fnName].subscribe(handler);
};
Emitter.prototype.listenAll = function (group, name, handler) {
    for (let prop in group) {
        var fnName = createName(prop);
        this.subjects[fnName] || (this.subjects[fnName] = new Rx.Subject());
        this.subjects[fnName].subscribe(group[prop]);
    }
};

Emitter.prototype.disposeOf = function (startWith) {
    var search = new RegExp('^\\$' + startWith);
    var subjects = this.subjects;
    for (let prop in subjects) {
        if (hasOwnProp.call(subjects, prop)) {
            if (search.test(prop)) {
                subjects[prop].dispose();
            }
        }
    }

    this.subjects = {};
};
Emitter.prototype.dispose = function () {
    var subjects = this.subjects;
    for (let prop in subjects) {
        if (hasOwnProp.call(subjects, prop)) {
            subjects[prop].dispose();
        }
    }

    this.subjects = {};
};
export default Emitter;
