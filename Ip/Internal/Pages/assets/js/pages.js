var ipPages = null;

(function ($) {
    "use strict";


    var app = angular.module('Pages', []).directive('zonesPostRepeatDirective', function () {
        return function (scope, element, attrs) {
            if (scope.$last) {
                pagesZones.init();
            }
        };
    });

    app.run(function ($rootScope) {
        $rootScope.$on('$locationChangeSuccess', function (e, newUrl, oldUrl) {
            $rootScope.$broadcast('PathChanged', newUrl);
        });
    });


    ipPages = function ($scope, $location) {



        //init
        $scope.activeLanguage = {id: null};
        $scope.activeMenu = {name: ''};
        $scope.copyPageId = false;
        $scope.cutPageId = false;
        $scope.selectedPageId = null;
        $scope.languages = languageList;
        $scope.zones = zoneList;
        $scope.initialized = false;

        $scope.$on('PathChanged', function (event, path) {
            var menuName = getHashParams().menu;
            var languageId = getHashParams().language;
            var navigationId = getHashParams().navigation;

            if (!$scope.initialized) {
                if (languageId == null) {
                    languageId = languageList[0].id;
                }
            }

            if (!menuName) {
                menuName = 'home';
            }

            if (languageId && languageId != $scope.activeLanguage.id) {
                $.each(languageList, function (key, value) {
                    if (value.id == languageId) {
                        $scope.activateLanguage(value);
                    }
                });
            }

            if (menuName && menuName != $scope.activeMenu.name) {
                $scope.activateMenu(menuName);
            }


            if (navigationId && navigationId != $scope.selectedPageId) {
                $scope.activatePage(navigationId, $scope.activeMenu.name);
            }

        });


        $scope.setZoneHash = function (zone) {
            updateHash(null, zone.name);
        }

        $scope.setLanguageHash = function (language) {
            updateHash(language.id);
        }


        $scope.activateLanguage = function (language) {
            $scope.activeLanguage = language;
            initTree();
        }

        $scope.activateMenu = function (menuName) {
            $scope.activeMenu = {'name': menuName};
            $scope.selectedPageId = null;
            initTree();
        }

        $scope.activatePage = function (navigationId, menuName) {
            $scope.selectedPageId = navigationId;
            var $properties = $('.ipsProperties');
            $properties.ipPageProperties({
                navigationId: navigationId,
                menuName: menuName
            });
            $properties.off('update.ipPages').on('update.ipPages', function () {
                var title = $properties.find('input[name=\'navigation[navigationTitle]\']').val();
                if (title == '') {
                    title = $properties.find('input[name=\'page[pageTitle]\']').val();
                }
                getJsTree().set_text(getJsTree().get_selected(), title);
            });
            $properties.off('delete.ipPages').on('delete.ipPages', function () {
                deletePage($scope.selectedPageId, function () {
                    getJsTree().remove(getJsTree().get_selected());
                });
            });
            $properties.off('edit.ipPages').on('edit.ipPages', function () {
                editPage($scope.selectedPageId);
            });

        }

        $scope.addPageModal = function () {
            var $modal = $('.ipsAddModal');
            $modal.find('input[name=title]').val('');
            $modal.modal();


            $modal.find('.ipsAdd').off('click').on('click', function () {
                $modal.find('form').submit()
            });
            $modal.find('form').off('submit').on('submit', function (e) {
                e.preventDefault();
                var title = $modal.find('input[name=title]').val();
                var visible = $modal.find('input[name=visible]').is(':checked') ? 1 : 0;
                addPage(title, visible);
                $modal.modal('hide');
            });
        }


        $scope.updateZoneModal = function (zone) {
            var $modal = $('.ipsUpdateZoneModal');
            $modal.modal();

            var data = {
                aa: 'Pages.updateZoneForm',
                menuName: zone.name
            }

            $.ajax({
                type: 'GET',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    $modal.find('.ipsBody').html(response.html);
                    $modal.find('.ipsDelete').off('click').on('click', function () {
                        $modal.find('.ipsDeleteConfirmation').removeClass('ipgHide');
                        $modal.find('.ipsBody').addClass('ipgHide');
                        $modal.find('.ipsDelete').addClass('ipgHide');
                        $modal.find('.ipsModalActions').addClass('ipgHide');
                        $modal.find('.ipsDeleteProceed').off('click').on('click', function () {
                            deleteZone(zone.name);
                        });
                    });
                    $modal.find('.ipsDeleteCancel').off('click').on('click', function () {
                        $modal.find('.ipsDeleteConfirmation').addClass('ipgHide');
                        $modal.find('.ipsBody').removeClass('ipgHide');
                        $modal.find('.ipsDelete').removeClass('ipgHide');
                        $modal.find('.ipsModalActions').removeClass('ipgHide');
                        $modal.find('.ipsDeleteProceed').off('click');
                    });

                    $modal.find('.ipsSave').off('click').on('click', function () {
                        $modal.find('form').submit()
                    });
                    $modal.find('form').off('submit').on('submit', function (e) {
                        e.preventDefault();
                        var title = $modal.find('input[name=title]').val();
                        var url = $modal.find('input[name=url]').val();
                        var name = $modal.find('input[name=name]').val();
                        var layout = $modal.find('select[name=layout]').val();
                        var metaTitle = $modal.find('input[name=metaTitle]').val();
                        var metaKeywords = $modal.find('input[name=metaKeywords]').val();
                        var metaDescription = $modal.find('textarea[name=metaDescription]').val();
                        var languageId = $scope.activeLanguage.id;
                        updateZone(zone.name, languageId, title, url, name, layout, metaTitle, metaKeywords, metaDescription);
                        $modal.modal('hide');
                    });

                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });


        }

        $scope.addZoneModal = function () {
            var $modal = $('.ipsAddZoneModal');
            $modal.find('input[name=title]').val('');
            $modal.modal();


            $modal.find('.ipsAdd').off('click').on('click', function () {
                $modal.find('form').submit()
            });
            $modal.find('form').off('submit').on('submit', function (e) {
                e.preventDefault();
                var title = $modal.find('input[name=title]').val();
                addZone(title);
                $modal.modal('hide');
            });
        }

        $scope.cutPage = function () {
            $scope.copyPageId = false;
            $scope.cutPageId = $scope.selectedPageId
        }

        $scope.copyPage = function () {
            $scope.cutPageId = false;
            $scope.copyPageId = $scope.selectedPageId;
        }


        $scope.zoneTitle = function (zone) {
            if (zone.title) {
                return zone.title;
            }
            return 'Untitled';
        }


        $scope.pastePage = function () {
            var tree = getJsTree();
            var position = tree._get_children(-1).length; //last position
            var node = tree.get_selected();
            if (node.length) {
                var position = node.index() + 1;
            }
            if ($scope.cutPageId) {
                movePage($scope.cutPageId, $scope.activeLanguage.id, $scope.activeMenu.name, $scope.selectedPageId, position, true);
            } else {
                copyPage($scope.copyPageId, $scope.activeLanguage.id, $scope.activeMenu.name, $scope.selectedPageId, position, function () {
                    refresh();
                });
            }

        }

        var initTree = function () {
            $scope.selectedPageId = null;
            getTreeDiv().ipPageTree({languageId: $scope.activeLanguage.id, menuName: $scope.activeMenu.name});
            getTreeDiv().off('select_node.jstree').on('select_node.jstree', function (e) {
                var node = getJsTree().get_selected();
                updateHash(null, null, node.attr('pageId'));
                $scope.$apply();
            });

            getTreeDiv().off('move_node.jstree').on('move_node.jstree', function (e, moveData) {
                moveData.rslt.o.each(function (i) {
                    var pageId = $(this).attr("pageId");
                    var destinationPageId = moveData.rslt.np.attr("pageId");
                    if (!destinationPageId) { //replace undefined with null;
                        destinationPageId = null;
                    }
                    var destinationPosition = moveData.rslt.cp + i;
                    movePage(pageId, $scope.activeLanguage.id, $scope.activeMenu.name, destinationPageId, destinationPosition);
                });
            });


        }


        var getTreeDiv = function () {
            return $('#pages_' + $scope.activeLanguage.id + '_' + $scope.activeMenu.name).find('.ipsTree');
        }

        var getJsTree = function () {
            return $.jstree._reference('#pages_' + $scope.activeLanguage.id + '_' + $scope.activeMenu.name + ' .ipsTree');
        }

        var refresh = function () {
            $('.ipsTree').ipPageTree('destroy');
            $scope.activateMenu($scope.activeMenu.name);
            $scope.$apply();
        }


        var addPage = function (title, visible) {
            var data = {
                aa: 'Pages.addPage',
                securityToken: ip.securityToken,
                title: title,
                visible: visible,
                menuName: $scope.activeMenu.name,
                languageId: $scope.activeLanguage.id
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    refresh();
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });

        }

        var addZone = function (title) {
            var data = {
                aa: 'Pages.addZone',
                securityToken: ip.securityToken,
                title: title
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    window.location = ip.baseUrl + '?aa=Pages.index';
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });

        }


        var editPage = function (navigationId, successCallback) {
            var data = {
                aa: 'Pages.getPageUrl',
                navigationId: navigationId
            };

            $.ajax({
                type: 'GET',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    window.location = response.pageUrl;
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }

        var deletePage = function (pageId, successCallback) {
            var data = {
                aa: 'Pages.deletePage',
                pageId: pageId,
                securityToken: ip.securityToken
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: successCallback,
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }


        var copyPage = function (pageId, destinationLanguageId, destinationZoneName, destinationParentId, destinationPosition, callback) {
            var data = {
                aa: 'Pages.copyPage',
                pageId: pageId,
                destinationParentId: destinationParentId,
                destinationPosition: destinationPosition,
                languageId: destinationLanguageId,
                zoneName: destinationZoneName,
                securityToken: ip.securityToken
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: callback,
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }

        var movePage = function (pageId, destinationLanguageId, destinationZoneName, destinationParentId, destinationPosition, doRefresh) {
            var data = {
                aa: 'Pages.movePage',
                pageId: pageId,
                destinationPosition: destinationPosition,
                destinationParentId: destinationParentId,
                languageId: destinationLanguageId,
                zoneName: destinationZoneName,
                securityToken: ip.securityToken
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    if (doRefresh) {
                        refresh();
                    }
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }

        var updateHash = function (languageId, menuName, navigationId) {
            if (languageId === null) {
                languageId = $scope.activeLanguage.id;
            }

            if (menuName === null) {
                menuName = $scope.activeMenu.name;
            }

            if (navigationId === null) {
                navigationId = $scope.selectedPageId;
            }

            var path = 'hash&language=' + languageId + '&menu=' + menuName;
            if (navigationId) {
                path = path + '&navigation=' + navigationId;
            }
            $location.path(path);
        }

        var updateZone = function (zoneName, languageId, title, url, name, layout, metaTitle, metaKeywords, metaDescription) {
            var data = {
                aa: 'Pages.updateZone',
                zoneName: zoneName,
                languageId: languageId,
                title: title,
                url: url,
                name: name,
                layout: layout,
                metaTitle: metaTitle,
                metaKeywords: metaKeywords,
                metaDescription: metaDescription,
                securityToken: ip.securityToken
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    window.location = ip.baseUrl + '?aa=Pages.index';
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }

        var deleteZone = function (zoneName) {
            var data = {
                aa: 'Pages.deleteZone',
                zoneName: zoneName,
                securityToken: ip.securityToken
            };

            $.ajax({
                type: 'POST',
                url: ip.baseUrl,
                data: data,
                context: this,
                success: function (response) {
                    window.location = ip.baseUrl + '?aa=Pages.index';
                },
                error: function (response) {
                    if (ip.developmentEnvironment || ip.debugMode) {
                        alert('Server response: ' + response.responseText);
                    }
                },
                dataType: 'json'
            });
        }


        var getHashParams = function () {

            var hashParams = {};
            var e,
                a = /\+/g,  // Regex for replacing addition symbol with a space
                r = /([^&;=]+)=?([^&;]*)/g,
                d = function (s) {
                    return decodeURIComponent(s.replace(a, " "));
                },
                q = window.location.hash.substring(1);

            while (e = r.exec(q))
                hashParams[d(e[1])] = d(e[2]);

            return hashParams;
        }


    }


})(ip.jQuery);


