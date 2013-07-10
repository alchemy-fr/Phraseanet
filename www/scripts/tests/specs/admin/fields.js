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

    describe("Admin field", function() {
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
                        {"id": 2, "sbas-id": sbasId, "name": "Object", "tag": "XMP:Object"}
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
                        this.collection.search('Cat')._wrapped.length.should.equal(1);
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
                    this.collection = new DcFieldCollection([{
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

                    this.view = new EditView({"model": model});
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
                    this.view.remove();
                });

                it("should render as a DIV element", function() {
                    this.view.render().el.nodeName.should.equal("DIV");
                    this.view.remove();
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

        describe("Edge cases", function() {
            beforeEach(function() {
                AdminFieldApp.fieldsCollection.add({"sbas-id": sbasId, "name": "Categorie", "tag": "XMP:Categorie"});
                AdminFieldApp.dcFieldsCollection.add({
                    "label": "Contributor",
                    "definition": "An entity responsible for making contributions to the resource.",
                    "URI": "http://dublincore.org/documents/dces/#contributor"
                });

                AdminFieldApp.saveView = new SaveView();
                AdminFieldApp.fieldErrorView = new FieldErrorView();
                AdminFieldApp.fieldListView = new ListItemView({
                    collection: AdminFieldApp.fieldsCollection,
                    el: AdminFieldApp.$leftBlock
                });
                // render views
                AdminFieldApp.saveView.render();
                AdminFieldApp.fieldListView.render();
            });

            it("should update collection when model change", function() {
                AdminFieldApp.fieldListView.itemViews[0].clickAction();
                AdminFieldApp.fieldEditView.model.set({
                    "name": "new name"
                });
                assert.equal(AdminFieldApp.fieldListView.itemViews[0].model.get('name'), "new name", 'model is updated');
            });

            it("should update edit view when clicking on single element", function() {
                AdminFieldApp.fieldListView.itemViews[0].clickAction();
                should.exist(AdminFieldApp.fieldEditView);
                assert.equal(AdminFieldApp.fieldEditView.model, AdminFieldApp.fieldListView.collection.first(), 'model is updated');
            });

            it("should reorder collection on drop action", function() {
                var ui = {item: {index: function() {return 2;}}};
                AdminFieldApp.fieldListView.itemViews[0].dropAction({},ui);
                assert.equal(AdminFieldApp.fieldListView.collection.last().get('sorter'), 3, 'model is updated');
            });
        });
    });
});
