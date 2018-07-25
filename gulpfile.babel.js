'use strict';

import gulp             from 'gulp';
import sourcemaps       from 'gulp-sourcemaps'; //inline source maps are embedded in the source file.
import babel            from 'gulp-babel'; //can use next generation JavaScript, today, with Babel
import minify           from 'gulp-minify'; //Minify JavaScript
import cleanCSS         from 'gulp-clean-css'; //gulp-clean-css
import autoprefixer     from 'gulp-autoprefixer'; //prefix CSS with Autoprefixer
import sass             from 'gulp-sass'; //Sass plugin for Gulp.
import path             from 'path'; //Creates file's paths in a simple and organized way
import rename           from 'gulp-rename'; //gulp-rename is a gulp plugin to rename files easily.
import less             from 'gulp-less'; //A LESS plugin for Gulp
import merge            from 'merge-stream'; //Merge (interleave) a bunch of streams.
import fs               from 'fs'; // filesystem binding
import through          from 'through2' // npm install --save through2

/**
 *there are folders for compiled
 */
const distDirs = [
    'themes',
    'plugins'
];

const PREPROCESSOR_SCSS = '*.+(scss|sass)';
const PREPROCESSOR_LESS = '*.less';
const PREPROCESSOR_CSS = '*.css';

const condition = (dir) => {
    return ['.', dir, '**', '.gulp.allow'].join(path.sep);
};

/**
 * Check is .gulp.allow file exists in theme or plugin root directory
 * @returns {*}
 */
let checkAllowed = () => {
    return through.obj(function (file, encoding, callback) {
        let pathRelative = file.path.substring(file.base.length, file.path.length);
        let pathFull = pathRelative.split(path.sep);
        let p = [file.base, pathFull[0], '.gulp.allow'].join(path.sep);
        if(fs.existsSync(p)) {
            this.push(file);
        }
        return callback();
    });
};

/**
 * this is const => getStaticDir for creating dir of compiled files
 */
const getStaticDir = () => {
    return rename((f) => {
        let pathFull = f.dirname.split(path.sep);
        const i = pathFull.lastIndexOf('src');
        if (i !== -1) {
            pathFull[i] = 'dist';
        }
        f.dirname = pathFull.join(path.sep);
    });
};
/**
 * this is const => getPublicDir for creating in public dir of compiled files
 */
const getPublicDir = () => {
    return rename((f) => {
        let pathFull = f.dirname.split(path.sep);
        const i = pathFull.lastIndexOf('static');
        if (i !== -1) {
            delete pathFull[i];
        }
        f.dirname = pathFull.join(path.sep);
    });
};

/**
 *
 * @param f
 * @returns {PassThrough}
 */
const render = (f) => {
    if (!f || typeof(f) !== 'function') {
        throw new Error('Callback function undefined');
    }
    let merger = merge();
    for (let i in distDirs) {
        if(distDirs.hasOwnProperty(i)) {
            let dirName = distDirs[i];
            merger.add(f(dirName));
        }
    }
    return merger;
};

/**
 *
 * @param preprocessor => it is for preprocessor
 * @returns {*}
 */
const buildCss = (preprocessor) => {
    if (!Boolean(preprocessor)) {
        return false;
    }
    return render((dir) => {
        let buildingProcess = gulp
            .src(['.', dir, '**', 'static', 'css', 'src', preprocessor].join(path.sep))
            .pipe(checkAllowed())
            .pipe(sourcemaps.init());
        switch (preprocessor) {
            case PREPROCESSOR_SCSS:
                buildingProcess = buildingProcess
                    .pipe(sass().on('error', sass.logError));
                break;
            case PREPROCESSOR_LESS:
                buildingProcess = buildingProcess
                    .pipe(less().on('error', (error) => {
                        throw new Error(error);
                    }));
                break;
            case PREPROCESSOR_CSS:
                // do nothing
                break;
            default:
                throw new Error('Not a preprocessor type');
        }

        return buildingProcess
            .pipe(autoprefixer(['last 15 versions', '> 1%', 'ie 8', 'ie 7'], {cascade: true}))
            .pipe(cleanCSS({compatibility: 'ie8'}))
            .pipe(sourcemaps.write('.'))
            .pipe(getStaticDir())
            .pipe(gulp.dest(['.', dir].join(path.sep)))
            .pipe(getPublicDir())
            .pipe(gulp.dest(['.', 'public_html', dir].join(path.sep)));
    });
};

/**
 *
 * @param dir => dir
 * @param mask => mask file (ex: scss)
 * @param tasks => task gulp
 */
const startWatch = (dir, mask, tasks) => {
    for (let i in distDirs) {
        if(distDirs.hasOwnProperty(i)) {
            let dirName = distDirs[i];
            gulp.watch(['.', dirName, '**', 'static', dir, 'src', mask].join(path.sep), tasks)
        }
    }
};


/**
 * gulp task compiled js
 */
gulp.task('js', () => {
    return render((dir) => {
        return gulp.src(['.', dir, '**', 'static', 'js', 'src', '*.js'].join(path.sep))
            .pipe(checkAllowed())
            .pipe(sourcemaps.init())
            .pipe(babel({'presets':['es2015']}))
            .pipe(sourcemaps.write('.'))
            .pipe(minify({
                ext:{
                    src:'-debug.js',
                    min:'.js'
                },
                exclude: ['tasks'],
                ignoreFiles: ['.combo.js', '-min.js', '.min.js']
            }))
            .pipe(getStaticDir())
            .pipe(gulp.dest(['.', dir].join(path.sep)))
            .pipe(getPublicDir())
            .pipe(gulp.dest(['.', 'public_html', dir].join(path.sep)));
    });
});

/**
 * gulp task compiled scss
 */
gulp.task('sass', () => {
    return buildCss(PREPROCESSOR_SCSS);
});

/**
 * gulp task compiled less
 */
gulp.task('less', () => {
    return buildCss(PREPROCESSOR_LESS);
});

/**
 * gulp task compiled css
 */
gulp.task('css', () => {
    return buildCss(PREPROCESSOR_CSS);
});

/**
 * gulp task watch preprocessor
 */
gulp.task('sass:watch', ['sass'], () => {
    return startWatch('css', '*.scss', ['sass']);
});

/**
 * gulp task watch preprocessor
 */
gulp.task('less:watch', ['less'], () => {
    return startWatch('css', '*.less', ['less']);
});

/**
 * gulp task watch preprocessor
 */
gulp.task('css:watch', ['css'], () => {
    return startWatch('css', '*.css', ['css']);
});

/**
 * gulp task watch preprocessor
 */
gulp.task('js:watch', ['js'], () => {
    return startWatch('js', '*.js', ['js']);
});


/**
 * gulp task watch all files in project
 */
gulp.task('watch', ['js:watch', 'sass:watch', 'less:watch', 'css:watch']);


/**
 * gulp task build all files in project
 */
gulp.task('build', ['js', 'sass', 'less', 'css']);