'use strict';

describe('LoginFormController', function(){
    var scope;
    beforeEach(inject(function($rootScope) {
        scope = $rootScope.$new();
        scope.isSubmitted = false;
        scope.loginForm = {
            login : {
                errors:{},
                $valid : true,
                $error : {
                    required : false
                }
            },
            password : {
                errors:{},
                $valid : true,
                $error : {
                    required : false
                }
            }
        };
    }));

    it('should create model errors with 2 validation for each input', inject(function($controller) {
        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(Object.keys(scope.loginForm.login.errors).length).toEqual(2);
        expect(Object.keys(scope.loginForm.password.errors).length).toEqual(2);
    }));

    it('should return input-table-error class when input is not valid', inject(function($controller) {
        scope.loginForm.login.errors.valid = false;
        scope.loginForm.password.errors.valid = true;

        $controller(LoginFormController, {
            $scope: scope
        });

        expect(scope.getInputClass('password')).toBe('');
        expect(scope.getInputClass('login')).toEqual('input-table-error');
    }));

    it('The valid login input validation should be equal to input form validation if form is submited and not valid', inject(function($controller) {
        scope.loginForm.$valid = false;

        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.loginForm.login.errors.valid).toBe(scope.loginForm.login.$valid);
    }));

    it('The filled password and login input validation should be true if input is not required and form is submited and not valid', inject(function($controller) {
        scope.loginForm.$valid = false;

        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.loginForm.login.errors.filled).not.toBe(scope.loginForm.login.$error.required);
        expect(scope.loginForm.password.errors.filled).not.toBe(scope.loginForm.password.$error.required);
    }));

    it('Should reset input validation errors when form is submited and valid', inject(function($controller) {
        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        var startLoginState = _.clone(scope.loginForm.login.errors);
        var startPasswordState = _.clone(scope.loginForm.password.errors);

        scope.loginForm.$valid = true;

        scope.loginForm.login.errors.valid = false;
        scope.loginForm.password.errors.valid = false;

        scope.submit();

        expect(scope.loginForm.login.errors).toEqual(startLoginState);
        expect(scope.loginForm.password.errors).toEqual(startPasswordState);
    }));

    it('should not submit form if it is not valid', inject(function($controller) {
        scope.loginForm.$valid = false;

        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.submit()).toBe(false);
    }));
});

describe('passwordChangeFormController', function(){
    var scope;
    beforeEach(inject(function($rootScope) {
        scope = $rootScope.$new();
        scope.isSubmitted = false;
        scope.passwordChangeForm = {
            passwordConfirm : {
                errors:{},
                $valid : true,
                $error : {
                    required : false
                }
            },
            password : {
                errors:{},
                $valid : true,
                $error : {
                    required : false
                }
            }
        };
    }));

    it('should create model errors with 2 validation for each input', inject(function($controller) {
        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(Object.keys(scope.passwordChangeForm.passwordConfirm.errors).length).toEqual(2);
        expect(Object.keys(scope.passwordChangeForm.password.errors).length).toEqual(2);
    }));

    it('should return input-table-error class when input is not valid', inject(function($controller) {
        scope.passwordChangeForm.passwordConfirm.errors.valid = false;
        scope.passwordChangeForm.password.errors.valid = true;

        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        expect(scope.getInputClass('password')).toBe('');
        expect(scope.getInputClass('passwordConfirm')).toEqual('input-table-error');
    }));

    it('The valid passwordConfirm input validation should be equal to input form validation if form is submited and not valid', inject(function($controller) {
        scope.passwordChangeForm.$valid = false;

        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.passwordChangeForm.passwordConfirm.errors.valid).toBe(scope.passwordChangeForm.passwordConfirm.$valid);
    }));

    it('The filled password and passwordConfirm input validation should be true if input is not required and form is submited and not valid', inject(function($controller) {
        scope.passwordChangeForm.$valid = false;

        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.passwordChangeForm.passwordConfirm.errors.filled).not.toBe(scope.passwordChangeForm.passwordConfirm.$error.required);
        expect(scope.passwordChangeForm.password.errors.filled).not.toBe(scope.passwordChangeForm.password.$error.required);
    }));

    it('Should reset input validation errors when form is submited and valid', inject(function($controller) {
        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        var startPasswordCondfirmState = _.clone(scope.passwordChangeForm.passwordConfirm.errors);
        var startPasswordState = _.clone(scope.passwordChangeForm.password.errors);

        scope.passwordChangeForm.$valid = true;

        scope.passwordChangeForm.passwordConfirm.errors.valid = false;
        scope.passwordChangeForm.password.errors.valid = false;

        scope.submit();

        expect(scope.passwordChangeForm.passwordConfirm.errors).toEqual(startPasswordCondfirmState);
        expect(scope.passwordChangeForm.password.errors).toEqual(startPasswordState);
    }));

    it('should not submit form if it is not valid', inject(function($controller) {
        scope.passwordChangeForm.$valid = false;

        $controller(passwordChangeFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.submit()).toBe(false);
    }));
});


describe('forgottenPasswordFormCtrl', function(){
    var scope;
    beforeEach(inject(function($rootScope) {
        scope = $rootScope.$new();
        scope.isSubmitted = false;
        scope.forgottenPasswordForm = {
            email : {
                errors:{},
                $valid : true,
                $error : {
                    required : false
                }
            }
        };
    }));

    it('should create model errors with 2 validation for email input', inject(function($controller) {
        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(Object.keys(scope.forgottenPasswordForm.email.errors).length).toEqual(2);
    }));

    it('should return input-table-error class when input is not valid', inject(function($controller) {
        scope.forgottenPasswordForm.email.errors.valid = true;

        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        expect(scope.getInputClass('email')).toBe('');
    }));

    it('The valid email input validation should be equal to input form validation if form is submited and not valid', inject(function($controller) {
        scope.forgottenPasswordForm.$valid = false;

        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.forgottenPasswordForm.email.errors.valid).toBe(scope.forgottenPasswordForm.email.$valid);
    }));

    it('The filled email input validation should be true if input is not required and form is submited and not valid', inject(function($controller) {
        scope.forgottenPasswordForm.$valid = false;

        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.forgottenPasswordForm.email.errors.filled).not.toBe(scope.forgottenPasswordForm.email.$error.required);
    }));

    it('Should reset input validation errors when form is submited and valid', inject(function($controller) {
        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        var startEmailState = _.clone(scope.forgottenPasswordForm.email.errors);

        scope.forgottenPasswordForm.$valid = true;

        scope.forgottenPasswordForm.email.errors.valid = false;

        scope.submit();

        expect(scope.forgottenPasswordForm.email.errors).toEqual(startEmailState);
    }));

    it('should not submit form if it is not valid', inject(function($controller) {
        scope.forgottenPasswordForm.$valid = false;

        $controller(forgottenPasswordFormCtrl, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.submit()).toBe(false);
    }));
});


