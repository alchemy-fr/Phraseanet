'use strict';

describe('LoginFormController', function(){
    var scope;
    beforeEach(inject(function($rootScope) {
        scope = $rootScope.$new();

        scope.loginForm = {
            email : {
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

        expect(Object.keys(scope.loginForm.email.errors).length).toEqual(2);
        expect(Object.keys(scope.loginForm.password.errors).length).toEqual(2);
    }));

    it('should return input-table-error class when input is not valid', inject(function($controller) {
        scope.loginForm.email.errors.valid = false;
        scope.loginForm.password.errors.valid = true;

        $controller(LoginFormController, {
            $scope: scope
        });

        expect(scope.getInputClass('password')).toBe('');
        expect(scope.getInputClass('email')).toEqual('input-table-error');
    }));

    it('The valid email input validation should be equal to input form validation if form is submited and not valid', inject(function($controller) {
        scope.loginForm.$valid = false;

        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.loginForm.email.errors.valid).toBe(scope.loginForm.email.$valid);
    }));

    it('The filled password and email input validation should be true if input is not required and form is submited and not valid', inject(function($controller) {
        scope.loginForm.$valid = false;

        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        expect(scope.loginForm.email.errors.filled).not.toBe(scope.loginForm.email.$error.required);
        expect(scope.loginForm.password.errors.filled).not.toBe(scope.loginForm.password.$error.required);
    }));

    it('Should reset input validation errors when form is submited and valid', inject(function($controller) {
        $controller(LoginFormController, {
            $scope: scope
        });

        scope.$digest();

        var startEmailState = _.clone(scope.loginForm.email.errors);
        var startPasswordState = _.clone(scope.loginForm.password.errors);

        scope.loginForm.$valid = true;

        scope.loginForm.email.errors.valid = false;
        scope.loginForm.password.errors.valid = false;
        
        scope.submit();

        expect(scope.loginForm.email.errors).toEqual(startEmailState);
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

