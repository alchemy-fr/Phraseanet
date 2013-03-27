// controllers
function LoginFormCtrl($scope) {
    $scope.isSubmitted = false;

    $scope.$watch('loginForm', function() {
        $scope.loginForm.login.errors = {'filled' : true, 'valid' : true};
        $scope.loginForm.password.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.loginForm.$valid) {
            $scope.isSubmitted = true;

            $scope.loginForm.login.errors = {'filled' : true, 'valid' : true};
            $scope.loginForm.password.errors = {'filled' : true,'valid' : true};

            return true;
        }

        $scope.loginForm.login.errors.filled = !$scope.loginForm.login.$error.required;

        $scope.loginForm.password.errors.filled = !$scope.loginForm.password.$error.required;

        return false;
    };

    $scope.getInputClass = function(name) {
        return _.every($scope.loginForm[name].errors, function(value) {
            return value === true;
        }) ? '' : 'input-table-error';
    };
}

function forgottenPasswordFormCtrl($scope) {
    $scope.isSubmitted = false;

    $scope.$watch('forgottenPasswordForm', function() {
        $scope.forgottenPasswordForm.email.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.forgottenPasswordForm.$valid) {
            $scope.forgottenPasswordForm.email.errors = {'filled' : true, 'valid' : true};
            $scope.isSubmitted = true;

            return true;
        }

        $scope.forgottenPasswordForm.email.errors.valid = $scope.forgottenPasswordForm.email.$valid;
        $scope.forgottenPasswordForm.email.errors.filled = !$scope.forgottenPasswordForm.email.$error.required;

        return false;
    };

    $scope.getInputClass = function(name) {
        return _.every($scope.forgottenPasswordForm[name].errors, function(value) {
            return value === true;
        }) ? '' : 'input-table-error';
    };
}

function passwordChangeFormCtrl($scope) {
    $scope.isSubmitted = false;

    $scope.$watch('passwordChangeForm', function() {
        $scope.passwordChangeForm.oldPassword.errors = {'filled' : true, 'valid' : true};
        $scope.passwordChangeForm.password.errors = {'filled' : true, 'valid' : true};
        $scope.passwordChangeForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.passwordChangeForm.$valid) {
            $scope.passwordChangeForm.oldPassword.errors = {'filled' : true, 'valid' : true};
            $scope.passwordChangeForm.password.errors = {'filled' : true, 'valid' : true};
            $scope.passwordChangeForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};

            $scope.isSubmitted = true;

            return true;
        }

        $scope.passwordChangeForm.password.errors.filled = !$scope.passwordChangeForm.password.$error.required;
        $scope.passwordChangeForm.passwordConfirm.errors.filled = !$scope.passwordChangeForm.passwordConfirm.$error.required;

        return false;
    };

    $scope.getInputClass = function(name) {
        return _.every($scope.passwordChangeForm[name].errors, function(value) {
            return value === true;
        }) ? '' : 'input-table-error';
    };
}

//@todo add tests
function registerFormCtrl($scope, $http) {
    $scope.isSubmitted = false;
    $scope.fields = [];

    $scope.$watch('registerForm', function() {
        $scope.registerForm.email.errors = {'filled' : true, 'valid' : true};
        $scope.registerForm.password.errors = {'filled' : true, 'valid' : true};
        $scope.registerForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};

        $http.get('/login/registration-fields').success(function(data, status) {
            $scope.fields = data;
            _.each($scope.fields, function(field){
                $scope.registerForm[camelize(field.name)].errors = {'filled' : true, 'valid' : true};
            });
        });
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.registerForm.$valid) {
            $scope.registerForm.email.errors = {'filled' : true, 'valid' : true};
            $scope.registerForm.password.errors = {'filled' : true, 'valid' : true};
            $scope.registerForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};

            _.each($scope.fields, function(field){
                $scope.registerForm[camelize(field.name)].errors = {'filled' : true, 'valid' : true};
            });

            $scope.isSubmitted = true;

            return true;
        }

        $scope.registerForm.password.errors.filled = !$scope.registerForm.password.$error.required;
        $scope.registerForm.passwordConfirm.errors.filled = !$scope.registerForm.passwordConfirm.$error.required;

        _.each($scope.fields, function(field){
            if (field.required) {
                $scope.registerForm[camelize(field.name)].errors.filled = !$scope.registerForm[camelize(field.name)].$error.required;
            }
        });

        return false;
    };

    $scope.getInputClass = function(name) {
        return _.every($scope.registerForm[name].errors, function(value) {
            return value === true;
        }) ? '' : 'input-table-error';
    };

}

function camelize(name) {
    return name.replace(/\-(\w)/g, function(all, letter, offset){
        return (offset === 0 && letter === 'w') ? 'w' : letter.toUpperCase();
    });
}

// bootstrap angular
angular.element(document).ready(function() {
    angular.bootstrap(document, ['phraseanetAuthentication']);
});

// angular app
angular.module('phraseanetAuthentication', ['ui'])
// force model update for autofill inputs. Yuck.
.directive('forceModelUpdate', function($compile) {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, element, attrs, ctrl) {
            scope.$on('event:force-model-update', function() {
                ctrl.$setViewValue(element.val());
            });
        }
    };
}).directive('checkFormSubmission', function () {
    // Angular does not prevent form submission if form is not valid  and if action attribute is defined.
    // This directive change angular's behavior by cancelling form submission
    // if form is not valid even if action attribute is defined
    return {
        link: function (scope, element, attrs, controller) {
            scope.$watch('isSubmitted', function(value) {
                if(false === value && !!element.attr('action')) {
                    element.bind('submit', function(event) {
                        event.preventDefault();
                        return false;
                    });
                } else {
                    element.unbind('submit').trigger('submit');
                }
            });
        }
    };
}).directive('phraseanetFlash', function () {
    return {
        restrict:'EA',
        template: [
            '<div class="alert" ng-class=\'type && "alert-" + type || "warning"\'>',
            '<table><tr>',
            '<td class="alert-block-logo"><i class="icon-2x icon-white" ng-class=\'icon || "icon-exclamation-sign"\'></i></td>',
            '<td class="alert-block-content" ng-transclude></td>',
            '<td class="alert-block-close"><a href="#"><b>&times;</b></a></td>',
            '</tr></table>',
            '</div>'
        ].join(''),
        transclude:true,
        replace:true,
        scope:{
            type: '@'
        },
        compile: function (element, attrs, transclude) {
            return function (scope, element, attr) {
                if (true === 'type' in attrs) {
                    switch (attrs.type) {
                        case 'error' :
                            scope.icon = 'icon-warning-sign';
                            break;
                        case 'success' :
                            scope.icon = 'icon-ok-sign';
                            break;
                        case 'info' :
                            scope.icon = 'icon-info-sign';
                            break;
                    }
                }
            };
        }
    };
});

