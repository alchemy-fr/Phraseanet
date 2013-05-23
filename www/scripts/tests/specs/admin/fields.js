define([
    'chai',
    'fixtures',
    'jquery',
    'app',
    'models/field',
    'apps/admin/fields/collections/fields',
    'apps/admin/fields/collections/dcFields',
    'apps/admin/fields/collections/vocabularies',
    'apps/admin/fields/views/listRow',
    'apps/admin/fields/views/list',
    'apps/admin/fields/views/alert',
    'apps/admin/fields/views/edit',
    'apps/admin/fields/views/fieldError',
    'apps/admin/fields/views/modal',
    'apps/admin/fields/views/save',
    'apps/admin/fields/views/dcField'
], function(
    chai,
    fixtures,
    $,
    App,
    FieldModel,
    FieldCollection,
    DcFieldCollection,
    VocabularyCollection,
    SingleItemView,
    ListItemView,
    AlertView,
    EditView,
    FieldErrorView,
    ModalView,
    SaveView,
    DcFieldView
) {
    var expect = chai.expect;
    var assert = chai.assert;
    var should = chai.should();

    // load fixtures in dom
    fixtures.path = 'fixtures';
    $("body").append(fixtures.read('admin/fields/dom', 'admin/fields/templates'));
    var sbasId = 1;

    App.initialize();

    describe("Application", function() {
        describe("Initialization", function() {
            it("should create a global variable", function() {
                should.exist(AdminFieldApp);
            });
        });

        describe("Collections", function() {
            describe("DcField Collection", function() {
                beforeEach(function() {
                    this.collection = new DcFieldCollection([]);
                });

                it("should set collection url according to provided 'sbas-id'", function() {
                    this.collection.url().should.equal("/admin/fields/dc-fields");
                });
            });

            describe("Vocabulary Collection", function() {
                beforeEach(function() {
                    this.collection = new VocabularyCollection([]);
                });

                it("should set collection url according to provided 'sbas-id'", function() {
                    this.collection.url().should.equal("/admin/fields/vocabularies");
                });
            });

            describe("Field Collection", function() {
                beforeEach(function() {
                    this.collection = new FieldCollection([
                        {"id": 1, "sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"},
                        {"id": 2, "sbas-id": sbasId, "name": "Object", "tag": "XMP:Object"},
                    ], {
                        "sbas_id": sbasId
                    });
                });

                describe("Initialization", function() {
                    it("should throw an exception if 'sbas_id' is missing", function() {
                        expect(function() {
                            new FieldCollection([]);
                        }).to.throw("You must set a sbas id");
                    });

                    it("should set collection url according to provided 'sbas-id'", function() {
                        this.collection.url().should.equal("/admin/fields/"+sbasId+"/fields");
                    });
                });

                describe("Methods", function() {
                    it("should retrieve categorie item if searching terms begins with 'cat'", function() {
                        this.collection.search('Cat').length.should.equal(1);
                    });

                    it("should retrieve previous and next index for given model", function() {
                        expect(this.collection.previousIndex(this.collection.first())).to.be.null;
                        this.collection.nextIndex(this.collection.first()).should.equal(1);
                        this.collection.previousIndex(this.collection.last()).should.equal(0);
                        expect(this.collection.nextIndex(this.collection.last())).to.be.null;
                    });
                });
            });
        });

        describe("Views", function() {
            describe("Single Item Views", function() {
                beforeEach(function() {
                    this.field = new FieldModel({"sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"});
                    this.view = new SingleItemView({model: this.field});
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a LI element", function() {
                    this.view.render().el.nodeName.should.equal("LI");
                });

                it("should render as a LI element with proper properties", function() {
                    this.view.render().$el.find('.field-name').html().should.equal("Categorie");
                    this.view.render().$el.find('.field-tag').html().should.equal("XMP:Categorie");
                });
            });

            describe("List Item Views", function() {
                beforeEach(function() {
                    this.collection = new FieldCollection([
                        {"sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"},
                        {"sbas-id": sbasId, "name": "Object", "tag": "XMP:Object"}
                    ], {
                        "sbas_id": sbasId
                    });

                    this.view = new ListItemView({
                        collection: this.collection,
                        el: AdminFieldApp.$leftBlock
                    });
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV block", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });

                it("should include list items for all models in collection", function() {
                    this.view.render();
                    this.view.$el.find("li").should.have.length(2);
                });
            });

            describe("Alert Views", function() {
                beforeEach(function() {
                    this.view = new AlertView({alert: "info", message: "Hello world!"});
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });

            describe("DcField Views", function() {
                beforeEach(function() {
                    this.collection = new DcFieldCollection([ {
                            "label": "Contributor",
                            "definition": "An entity responsible for making contributions to the resource.",
                            "URI": "http://dublincore.org/documents/dces/#contributor"
                        }, {
                            "label": "Coverage",
                            "definition": "The spatial or temporal topic of the resource, the spatial applicability of the resource,\n                          or the jurisdiction under which the resource\n                          is relevant.",
                            "URI": "http://dublincore.org/documents/dces/#coverage"
                        }
                    ]);

                    var model = new FieldModel({"id": 1, "sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"});

                    this.view = new DcFieldView({
                        "collection": this.collection,
                        "field": model
                    });
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });

            describe("Edit Views", function() {
                beforeEach(function() {
                    var model = new FieldModel({"id": 1, "sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"});

                    this.view = new EditView({"model": model}).render();
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });

            describe("FieldError Views", function() {
                beforeEach(function() {
                    this.view = new FieldErrorView();
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });

            describe("Modal Views", function() {
                beforeEach(function() {
                    var model = new FieldModel({"id": 1, "sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"});

                    this.view = new ModalView({
                        model: model,
                        message: "hellow world!"
                    });
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });

            describe("Save Views", function() {
                beforeEach(function() {
                    this.view = new SaveView();
                });

                it("render() should return the view object", function() {
                    this.view.render().should.equal(this.view);
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                });
            });
        });
    });
});
