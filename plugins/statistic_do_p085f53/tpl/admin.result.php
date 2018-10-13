<?php
/** @var $this Plugin_Statistic_Do_p085f53 */
$times = array(
    'week'      => array('t' => $this->langAdmin('неделю')),
    'month'     => array('t' => $this->langAdmin('месяц')),
    'quarter'   => array('t' => $this->langAdmin('квартал')),
    'year'      => array('t' => $this->langAdmin('год')),
);
foreach($times as $k => & $v) {
    $v['id'] = $k;
}unset($v);
$tfst = reset($times);
$pageSettings = array(
    'icon'=>'icon-signal',
    'title' => $this->langAdmin('Статистика проекта'),
);
if ($updated_drive > 0) {
    $pageSettings['link'] = array(
        'title' => $this->langAdmin('последнее обновление: [date]', ['date' => tpl::dateFormat((int)$updated_drive, '%d.%m.%Y %H:%M')]),
        'href' => 'javascript:void(0);',
        'style' => 'text-decoration: none; cursor: default; color: #999898 !important;',
    );
}
tplAdmin::adminPageSettings($pageSettings);
?>
<style>
    .axis {
        font: 11px sans-serif;
    }

    path.line {
        stroke: #0088CC;
        stroke-width: 2;
        fill: none;
    }
    circle {
        fill: none;
        stroke-width: 0;
    }
    circle.nearest {
        stroke: #6BB034;
        fill: #6BB034;
        stroke-width: 3;
    }

    .axis path,
    .axis line {
        fill: none;
        stroke: grey;
        stroke-width: 1;
        shape-rendering: crispEdges;
    }
    .bar {
        fill: #0088CC;
    }
    .bar:hover {
        fill: #6BB034;
    }
    .white {
        fill: white;
    }
</style>
<?  $legendHTML = function($title = false) { ob_start(); ob_implicit_flush(false); if (empty($title)) $title = $this->langAdmin('Новые'); ?>
    <div class="a-graphic-info j-legend" style="display: none;">
        <div class="a-graphic-info-date j-date" style="white-space: nowrap;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
        <div class="a-graphic-numbers">
            <div class="a-graphic-num-item">
                <div class="a-graphic-title text-blue"><?= $title ?>:</div>
                <div class="a-graphic-num j-val">&nbsp;&nbsp;&nbsp;</div>
            </div>
        </div>
    </div>
<? return ob_get_clean(); } ?>
<? $widget = function($d) use (& $times, $tfst) { ?>
    <div class="a-widget span-6">
        <div class="box-content">
            <div class="l-box-subheader">
                <h3 class="a-widget-title">
                    <a href="<?= $d['url'] ?>"><?= $d['title'] ?></a>
                </h3>
            </div>
            <hr>

            <div class="a-statistic-widget-top">
                <div class="a-statistic-l j-pie-<?= $d['module'] ?>" style="min-width: 150px;"></div>
                <div class="a-statistic-r">
                    <? foreach($d['counts'] as $v): ?>
                        <div class="a-statistic-type-item">
                            <div class="a-statistic-text">
                                <div class="a-widget-dot" style="background-color: <?= $v['c'] ?>;"></div>
                                <a class="a-section-type" href="<?= $v['url'] ?>"><?= $v['t'] ?></a>
                                <? if ( ! empty($v['tooltip'])): ?>
                                    <a class="c-icon-question-sign show-tooltip" data-toggle="tooltip" data-placement="right" data-html="true" title="<?= HTML::escape($v['tooltip']) ?>">
                                        <img src="<?= $this->url('question.png'); ?>" alt="">
                                    </a>
                                <? endif; ?>
                            </div>
                            <div class="a-statistic-type-num"><?= $v['count'] ?></div>
                        </div>
                    <? endforeach; ?>
                </div>
            </div>

            <? if ( ! empty($d['week'])): ?>
            <div class="a-statistic-widget-bottom j-chart-bl" data-module="<?= $d['module'] ?>">
                <div class="a-statistic-controls">
                    <div class="a-statistic-title"><?= $this->langAdmin('Новые за')?></div>

                    <div class="dropdown">
                        <a class="a-visits" href="#" data-toggle="dropdown">
                            <span class="j-time-selected"><?= $tfst['t'] ?></span>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <? foreach($times as $k => $v): ?>
                                <li><a href="javascript:" class="j-time<?= $tfst['id'] == $k ? ' active' : '' ?>" data-time="<?= $k ?>"><?= $v['t'] ?></a></li>
                            <? endforeach; ?>
                        </ul>
                    </div>

                    <div class="a-graphic-toggles btn-group btn-group-sm">
                        <a class="btn btn-default active j-type" href="javascript:" data-type="bar">
                            <img class="ico" src="<?= $this->url('dg.png'); ?>" alt="">
                            <img class="ico" src="<?= $this->url('dg-active.png'); ?>" alt="">
                        </a>
                        <a class="btn btn-default j-type" href="javascript:"  data-type="line">
                            <img class="ico" src="<?= $this->url('inc-dg.png'); ?>" alt="">
                            <img class="ico" src="<?= $this->url('inc-dg-active.png'); ?>" alt="">
                        </a>
                    </div>
                </div>
                <div class="a-statistic-graphic-wrap a-test-statistic-info">
                    <div class="j-new-<?= $d['module'] ?> j-chart" data-time="<?= $tfst['id'] ?>" data-type="bar"></div>
                </div>
            </div>
            <? endif; ?>
        </div>
    </div>
<? } ?>
<? if (empty($bbs['week']) || empty($users['week']) || empty($usage[$this::TYPE_USAGE_CODE])): ?>
    <div class="alert alert-info" style="margin-bottom: 10px;"><?= $this->langAdmin('Выполняется подготовка данных для статистики...<br />Пожалуйста, обновите страницу через несколько минут.') ?></div>
<? endif; ?>
<div class="row-widgets" id="j-statistic-bl">
    <? $widget($bbs); ?>
    <? $widget($users); ?>
    <? if (! empty($shops)) { $widget($shops); } ?>
    <div class="a-widget <?= empty($shops) ? 'span-12' : 'span-6' ?>">
        <div class="box-content">
            <div class="l-box-subheader">
                <h3 class="a-widget-title">
                    <?= $this->langAdmin('Место на диске'); ?>
                </h3>
            </div>
            <hr>
            <div class="<?= empty($shops) ? 'a-memory-wrap' : 'a-memory-wrap a-memory-vertical' ?>">
                <div class="a-memory-top-info">
                    <div class="a-memory-item">
                        <div class="a-memory-title"><?= $this->langAdmin('Занято проектом'); ?></div>
                        <div class="a-memory-num"><?= tpl::filesize($size['used']); ?></div>
                    </div>
                    <div class="a-memory-item">
                        <div class="a-memory-title"><?= $this->langAdmin('Свободно'); ?></div>
                        <div class="a-memory-num"><?= tpl::filesize($size['free']); ?></div>
                    </div>
                </div>

                <div class="a-memory-total">
                    <div class="a-memory-total-num"><?= tpl::filesize($size['total']); ?></div>
                </div>

                <div class="a-memory-bar">
                    <? foreach($size['bar'] as $v): ?>
                        <div class="a-memory-progress" style="width: <?= $v['p'] ?>%;background-color: <?= $v['c'] ?>"></div>
                    <? endforeach; ?>
                </div>

                <div class="a-memory-categories">
                    <? foreach($size['legend'] as $v): ?>
                    <div class="a-memory-cat-item">
                        <div class="a-widget-dot" style="background-color: <?= $v['c'] ?>"></div>
                        <div class="a-memory-cat-in">
                            <div class="a-memory-cat-title"><?= $v['t'] ?></div>
                            <div class="a-memory-cat-num"><?= tpl::filesize($v['s']); ?></div>
                        </div>
                    </div>
                    <? endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="span-6 displaynone"></div>
</div>
<script type="text/javascript">
$(function(){
    var $block = $('#j-statistic-bl');
    <? $month = _t('view', 'янв,фев,мар,апр,май,июн,июл,авг,сен,окт,ноя,дек');
    $month = explode(',', $month);
    $short = array();
    foreach($month as $k => $v) {
        $short[] = ($k + 1).':"'.$v.'"';
    }
    $month = _t('view', 'Января,Февраля,Марта,Апреля,Мая,Июня,Июля,Августа,Сентября,Октября,Ноября,Декабря');
    $month = explode(',', $month);
    $full = array();
    foreach($month as $k => $v) {
        $full[] = ($k + 1).':"'.mb_strtolower($v).'"';
    }
    $month = _t('view', 'Январь,Февраль,Март,Апрель,Май,Июнь,Июль,Август,Сентябрь,Октябрь,Ноябрь,Декабрь');
    $month = explode(',', $month);
    $nominative = array();
    foreach($month as $k => $v) {
        $nominative[] = ($k + 1).':"'.mb_strtolower($v).'"';
    }
    ?>
    var month = {<?= join(',', $short) ?>}, monthFull = {<?= join(',', $full) ?>}, monthNominative = {<?= join(',', $nominative) ?>};

    var pieChart = function (o)
    {
        var $block = $(o.block);

        var width = $block.width(),
            height = $block.width(),
            radius = Math.min(width, height) / 2;

        var arc = d3.svg.arc()
            .outerRadius(radius - 10)
            .innerRadius(radius - 40);

        var pie = d3.layout.pie()
            .sort(null)
            .value(function(d) { return d.count; });

        var svg = d3.select($block.get(0)).append('svg')
            .attr('width', width)
            .attr('height', height)
            .append('g')
            .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

        var g = svg.selectAll('.arc')
            .data(pie(o.data))
            .enter().append('g')
            .attr('class', 'arc');

        g.append('path')
            .attr('d', arc)
            .style('fill', function(d) {
                return d.data.c;
            });
    };

    var barChart = function (o)
    {
        var maxY = d3.max(o.data, function(d) { return intval(d.c); });

        var $block = $(o.block);
        var margin = {top: 20, right: 5, bottom: 90, left: maxY > 9999 ? 70 : 40};
        var	parseDate = d3.time.format('%Y-%m-%d').parse;

        var beg = {}, noday = false;
        o.data.map(function(d) {
            if (d.hasOwnProperty('f')) {
//                margin.bottom = 90;
//                margin.top = 0;
                beg[parseDate(d.d)] = d.f;
            }
            if (d.hasOwnProperty('m')) {
                noday = true;
            }
        });

        var width = $block.width() - margin.left - margin.right,
            height = intval($block.width() / 2);

        var x = d3.scale.ordinal().rangeRoundBands([0, width], .4);
        var y = d3.scale.linear().range([height, 0]);

        var dateText = function (d, i, full) {
            var res = '';
            var day = d3.time.format('%d')(d);
            var dd = intval(d3.time.format('%m')(d));
            var mon = full ? (noday ? monthNominative[dd] : monthFull[dd]) : month[dd];
            var year = full ? d3.time.format('%Y')(d) : d3.time.format('%y')(d);

            if (noday) {
                res = mon + ' ' + year;
            } else if (beg.hasOwnProperty(d)) {
                res = beg[d] + '-' + day + ' ' + mon + ' ' + year;
            } else {
                res = day + ' ' + mon + ' ' + year;
            }
            return res;
        };

        var xAxis = d3.svg.axis()
            .scale(x)
            .orient('bottom')
            .tickFormat(dateText);

        var yAxis = d3.svg.axis()
            .scale(y)
            .orient('left')
            .ticks(maxY < 10 ? maxY : 10, 'd');

        var svg = d3.select($block.get(0)).append('svg')
            .attr('width', width + margin.left + margin.right)
            .attr('height', height + margin.top + margin.bottom)
            .append('g')
            .attr('transform',
                'translate(' + margin.left + ',' + margin.top + ')');

        x.domain(o.data.map(function(d) { return parseDate(d.d); }));
        y.domain([0, maxY]);

        svg.append('g')
            .attr('class', 'x axis')
            .attr('transform', 'translate(0,' + height + ')')
            .call(xAxis)
            .selectAll('text')
            .style('text-anchor', 'end')
            .attr('dx', '-.8em')
            .attr('dy', '-.55em')
            .attr('transform', 'rotate(-90)');

        svg.append('g')
            .attr('class', 'y axis')
            .call(yAxis);

        svg.selectAll('bar')
            .data(o.data)
            .enter().append('rect').attr('class', 'bar')
            .attr('x', function(d) { return x(parseDate(d.d)); })
            .attr('width', x.rangeBand())
            .attr('y', function(d) { return y(d.c); })
            .attr('height', function(d) { return height - y(d.c); })
            .attr('data-d', function(d) { return dateText(parseDate(d.d), 0, true); })
            .attr('data-c', function(d) { return d.c; });

        $block.append(<?= func::php2js($legendHTML()) ?>);
        var $legend = $block.find('.j-legend');
        var pos = $block.offset();
        $block.on('mouseenter', '.bar', function (e) {
            var $el = $(this);
            $legend.find('.j-date').text($el.data('d'));
            $legend.find('.j-val').text($el.data('c'));
            $legend.show();
        }).on('mouseleave', '.bar', function (e) {
            $legend.hide();
        }).on('mousemove', '.bar', function (e) {
            $legend.css({
                'top':e.pageY - pos.top + 10,
                'left':e.pageX - pos.left + 10
            });
        });
    };

    var lineChart = function (o)
    {
        var $block = $(o.block);

        var maxY = d3.max(o.data, function(d) { return intval(d.c); });
        var	margin = {top: 20, right: 5, bottom: 90, left: maxY > 9999 ? 70 : 40},
            width = $block.width() - margin.left - margin.right,
            height = intval($block.width() / 2);

        var parseTime = d3.time.format('%Y-%m-%d').parse;

        var dateText = function (d, i, full) {
            var day = d3.time.format('%d')(d);
            var dd = intval(d3.time.format('%m')(d));
            var mon = full ? monthFull[dd] : month[dd];
            var year = full ? d3.time.format('%Y')(d) : d3.time.format('%y')(d);
            return day + ' ' + mon + ' ' + year;
        };

        var	x = d3.time.scale().range([0, width]);
        var y = d3.scale.linear().range([height, 0]);

        var cntX = o.data.length;
        var	xAxis = d3.svg.axis().scale(x)
            .orient('bottom')
            .ticks(cntX > 15 ? 15 : cntX)
            .tickFormat(dateText);

        var	yAxis = d3.svg.axis().scale(y)
            .orient('left')
            .ticks(maxY < 10 ? maxY : 10, 'd');

        var	line = d3.svg.line()
            .x(function(d) { return x(parseTime(d.d)); })
            .y(function(d) { return y(d.c); });

        var	svg = d3.select($block.get(0))
            .append('svg')
            .attr('width', width + margin.left + margin.right)
            .attr('height', height + margin.top + margin.bottom)
            .append('g')
            .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

        x.domain(d3.extent(o.data, function(d) { return parseTime(d.d); }));
        y.domain([0, maxY]);

        svg.append('rect')
            .attr('class', 'white')
            .attr('width', width)
            .attr('height', height);

        svg.append('g')
            .attr('class', 'x axis')
            .attr('transform', 'translate(0,' + height + ')')
            .call(xAxis)
            .selectAll('text')
            .style('text-anchor', 'end')
            .attr('dx', '-.8em')
            .attr('dy', '-.55em')
            .attr('transform', 'rotate(-90)' );

        svg.append('g')
            .attr('class', 'y axis')
            .call(yAxis);

        svg.append('path')
            .attr('class', 'line')
            .attr('d', line(o.data));

        svg.selectAll('dot')
            .data(o.data)
            .enter().append('circle').attr('class', 'dot')
            .attr('r', 2)
            .attr('cx', function(d) { return x(parseTime(d.d)); })
            .attr('cy', function(d) { return y(d.c); });

        $block.append(<?= func::php2js($legendHTML()) ?>);
        var $legend = $block.find('.j-legend');
        $legend.show();
        var shX = intval($legend.outerWidth(true) / 2) + 10;
        var shY = intval($legend.outerHeight(true) / 2);
        $legend.hide();
        var dmax = $block.width();
        svg.on('mousemove', function () {
                var m = d3.mouse(this);
                var min = dmax;
                var dmin, dd;
                $legend.hide();
                svg.selectAll('.dot').each(function (d) {
                    var el = d3.select(this);
                    el.classed('nearest', false);
                    var lx = m[0] - intval(el.attr('cx'));
                    var ly = m[1] - intval(el.attr('cy'));
                    var l = Math.sqrt(lx * lx + ly * ly);
                    if (l < min) {
                        min = l;
                        dmin = el;
                        dd = d;
                    }
                });
                dmin.classed('nearest', true);
                $legend.find('.j-date').text(dateText(parseTime(dd.d), 0, true));
                $legend.find('.j-val').text(dd.c);
                $legend.show();
                $legend.css({
                    'top':m[1] + shY,
                    'left':m[0] + shX
                });
            })
           .on('mouseleave', function () {
                $legend.hide();
                svg.select('.nearest').classed('nearest', false);
            });
    };

    $block.on('click', '.j-time', function (e) {
        e.preventDefault();
        var $el = $(this);
        var $bl = $el.closest('.j-chart-bl');
        $bl.find('.j-time').removeClass('active');
        $el.addClass('active');
        $bl.find('.j-time-selected').text($el.text());
        showChart($el);
    });

    $block.on('click', '.j-type', function (e) {
        e.preventDefault();
        var $el = $(this);
        $el.addClass('active').siblings().removeClass('active');
        showChart($el);
    });

    function showChart($el)
    {
        var $bl = $el.closest('.j-chart-bl');
        if ( ! $bl.length) return;
        var module = $bl.data('module');
        var time = $bl.find('.j-time.active').data('time');
        var type = $bl.find('.j-type.active').data('type');
        var $chart = $bl.find('.j-chart');
        bff.ajax('<?= $this->adminLink(bff::$event) ?>&act=chart-data',{module:module, time:time, type:type}, function (data) {
            if (data && data.success) {
                $chart.html('');
                if (type == 'bar') {
                    barChart({block:$chart, data:data.data});
                } else {
                    lineChart({block:$chart, data:data.data});
                }
            }
        }, function (d) {
            $chart.toggleClass('disabled', d);
        });
    }

    $('.show-tooltip').tooltip();

    pieChart({
        block:$block.find('.j-pie-bbs'),
        data:<?= func::php2js($bbs['counts']) ?>
    });

    pieChart({
        block:$block.find('.j-pie-users'),
        data:<?= func::php2js($users['counts']) ?>
    });

    <? if ( ! empty($bbs['week'])): ?>
        barChart({
            block:$block.find('.j-new-bbs'),
            data:<?= func::php2js($bbs['week']) ?>
        });
    <? endif; ?>
    <? if ( ! empty($users['week'])): ?>
        barChart({
            block:$block.find('.j-new-users'),
            data:<?= func::php2js($users['week']) ?>
        });
    <? endif; ?>

    <? if ( ! empty($shops)): ?>
        pieChart({
            block:$block.find('.j-pie-shops'),
            data:<?= func::php2js($shops['counts']) ?>
        });
        <? if ( ! empty($shops['week'])): ?>
            barChart({
                block:$block.find('.j-new-shops'),
                data:<?= func::php2js($shops['week']) ?>
            });
        <? endif; ?>
    <? endif; ?>
});
</script>