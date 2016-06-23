var gulp = require("gulp")
var uglify = require("gulp-uglify")
var rename = require("gulp-rename")

gulp.task("default", function () {
  gulp.src("./assets/js/modules/*.js")
      .pipe(uglify())
      .pipe(rename({
        "suffix": ".min"
        }))
      .pipe(gulp.dest("assets/js/modules/min"))
});