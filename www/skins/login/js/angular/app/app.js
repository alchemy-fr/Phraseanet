// controllers
function LoginFormController($scope) {
    $scope.$watch('loginForm', function() {
        $scope.loginForm.email.errors = {'filled' : true, 'valid' : true};
        $scope.loginForm.password.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.loginForm.$valid) {
            $scope.loginForm.email.errors = {'filled' : true, 'valid' : true};
            $scope.loginForm.password.errors = {'filled' : true,'valid' : true};
            // submit
            return true;
        }

        $scope.loginForm.email.errors.valid = $scope.loginForm.email.$valid;
        $scope.loginForm.email.errors.filled = !$scope.loginForm.email.$error.required;

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
    $scope.$watch('forgottenPasswordForm', function() {
        $scope.forgottenPasswordForm.email.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.forgottenPasswordForm.$valid) {
            $scope.forgottenPasswordForm.email.errors = {'filled' : true, 'valid' : true};
            // submit
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
    $scope.$watch('passwordChangeForm', function() {
        $scope.passwordChangeForm.password.errors = {'filled' : true, 'valid' : true};
        $scope.passwordChangeForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};
    });

    $scope.submit = function() {
        $scope.$broadcast('event:force-model-update');

        if (true === $scope.passwordChangeForm.$valid) {
            $scope.passwordChangeForm.password.errors = {'filled' : true, 'valid' : true};
            $scope.passwordChangeForm.passwordConfirm.errors = {'filled' : true, 'valid' : true};
            // submit
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
    }
}).directive('alert', function () {
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
            }
        }
    };
});

