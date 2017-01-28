<link rel="stylesheet" href="/static/bower_components/ngWig/dist/css/ng-wig.css" />
<script src="/static/bower_components/ngWig/dist/ng-wig.js"></script>

<script>
    angular.module('ngWig')
        .config(['ngWigToolbarProvider', function (ngWigToolbarProvider) {
            ngWigToolbarProvider.addCustomButton('clickme', 'click-me-button');
            ngWigToolbarProvider.addCustomButton('inserTag', 'insert-tag-button');
            //ngWigToolbarProvider.setButtons(['bold', 'italic', 'link', 'clickme', 'list1', 'list2']);
        }])
        .component('clickMeButton', {
            template: '<button class="nw-button fa fa-fw fa-hand-pointer-o" title="Click me" ng-click="insert($event)">Click me button</button>',
            controller: function ($scope) {
                $scope.insert = function (e) {
                    var ngWigElement = e.target.parentElement.parentElement.parentElement.parentElement.parentElement;
                    if (ngWigElement) {
                        var container = angular.element(ngWigElement.querySelector('#ng-wig-editable'));
                        var link = '{auth}/';
                        if (link = prompt('Click me button', link)) {
                            var btn = '<a href="' + link + '"><img src="https://i.imgur.com/fPtAGeP.png" title="More information" alt="More information" width="117" height="34"></a>';
                            container.html(container.html() + btn);
                            container[0].focus();
                        }
                    }
                };
            }
        })
        .component('insertTagButton', {
            template: '<button class="nw-button fa fa-fw fa-tag" title="Insert tag" ng-click="insert($event)">Insert tag</button>',
            controller: function ($scope) {
                $scope.insert = function (e) {
                    var ngWigElement = e.target.parentElement.parentElement.parentElement.parentElement.parentElement;
                    if (ngWigElement) {
                        var container = angular.element(ngWigElement.querySelector('#ng-wig-editable'));
                        var link = '{auth}/members';
                        var btn = '<p><a href="' + link + '">{first_name} member\'s area</a></p>';
                        container.html(container.html() + btn);
                        container[0].focus();
                    }
                };
            }
        });
</script>