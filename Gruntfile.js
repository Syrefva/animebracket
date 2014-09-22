module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            js: {
                options: {
                    separator: ';'
                },
                src: [
                    'static/js/dev/lib/jquery-1.10.2.min.js',
                    'static/js/dev/lib/handlebars.runtime.js',
                    'static/js/dev/lib/templates.js',
                    'static/js/dev/controls/*.js',
                    'static/js/dev/model/*.js',
                    'static/js/dev/*.js'
                ],
                dest: 'static/js/<%= pkg.name %>.js'
            },
            scss: {
                src: [
                    'static/css/dev/compile/*.css'
                ],
                dest: 'static/css/<%= pkg.name %>.css'
            }
        },
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    'static/js/<%= pkg.name %>.min.js': ['<%= concat.js.dest %>']
                }
            }
        },
        watch: {
            files: [
                'static/js/dev/model/*.js',
                'static/js/dev/controls/*.js',
                'static/js/dev/*.js',
                'static/css/dev/*.scss',
                'view/handlebars/*.handlebars'
            ],
            tasks: [ 'handlebars', 'sass', 'concat', 'cssmin' ]
        },
        handlebars: {
            compile: {
                options: {
                    namespace: 'Templates',
                    wrapped:true,
                    processName: function(filename) {
                        filename = filename.split('/');
                        filename = filename[filename.length - 1];
                        return filename.split('.')[0];
                    }
                },
                files: {
                    'view/js/lib/templates.js': 'view/handlebars/*.handlebars'
                }
            }
        },
        sass: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'static/css/dev',
                    src: [ '*.scss' ],
                    dest: 'static/css/dev/compile',
                    ext: '.css'
                }]
            }
        },
        cssmin: {
            dist: {
                options: {
                    banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
                },
                files: {
                    'static/css/<%= pkg.name %>.min.css': [ '<%= concat.scss.dest %>' ]
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-handlebars');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    grunt.registerTask('default', ['handlebars', 'sass', 'concat', 'uglify', 'cssmin' ]);

};