'use strict';

{
  var colors = ['rgba(96, 196, 150, 1)', 'rgba(80, 150, 215, 1)', 'rgba(0, 188, 212, 1)', 'rgba(116, 199, 209, 1)', 'rgba(255, 82, 82, 1)', 'rgba(0, 0, 0, 0)'];

  var data = [{
    'key': 'WKM',
    'y': 0,
    'end': 170
  }, {
    'key': 'WrKM',
    'y': 0,
    'end': 130
  }, {
    'key': 'BKM',
    'y': 0,
    'end': 50
  }, {
    'key': 'GDANSK',
    'y': 0,
    'end': 150
  }, {
    'key': 'ALL',
    'y': 500
  }];

  nv.addGraph(function () {
    var innerRadius = 0.86,
        outerRadius = 1.02;

    var pieChart = nv.models.pieChart().x(function (d) {
      return d.key;
    }).y(function (d) {
      return d.y;
    }).showLabels(false).donut(true).growOnHover(true).padAngle(.04).cornerRadius(0).margin({ 'left': -10, 'right': -10, 'top': -10, 'bottom': -10 }).color(colors).arcsRadius([{ 'inner': innerRadius, 'outer': outerRadius }, { 'inner': innerRadius, 'outer': outerRadius }, { 'inner': innerRadius, 'outer': outerRadius }, { 'inner': innerRadius, 'outer': outerRadius }, { 'inner': innerRadius, 'outer': outerRadius }]).showLegend(false).title('0 interwencji').titleOffset(0);

    pieChart.tooltip.enabled(true).hideDelay(0).headerEnabled(false).contentGenerator(function (d) {
      if (d === null) {
        return '';
      }
      d3.selectAll('.nvtooltip').classed('mdl-tooltip', true);
      return d.data.y + ' interwencji';
    });

    var container = d3.select('.pie-chart__container').append('div').append('svg').datum(data).transition().duration(1200).call(pieChart);

    var h = 0,
        i = 0;
    var timer = setInterval(animatePie, 10, data);

    function animatePie(data) {
      if (i < data.length - 1) {
        if (data[i].y < data[i].end) {
          data[i].y++;
          data[data.length - 1].y--;
          pieChart.title(h + 1 + ' ');
          h++;
        } else {
          i++;
        }
      } else {
        data.splice(data.length - 1, 1);
        clearInterval(timer);
        return;
      }
      if (container[0][0]) {
        pieChart.update();
      } else {
        clearInterval(timer);
      }
    }

    //d3.select('.nv-pie .nv-pie').append('image').attr('width', '30').attr('height', '30').attr('xlink:href', 'images/watch_white.svg').attr('transform', 'translate(-15,-35)');

    var color = d3.scale.ordinal().range(colors);

    var legend = d3.select('.pie-chart__container').append('div').attr('class', 'legend').selectAll('.legend__item').data(data.slice(0, data.length - 1)).enter().append('div').attr('class', 'legend__item');

    legend.append('div').attr('class', 'legend__mark pull-left').style('background-color', function (d) {
      return color(d.key);
    });

    legend.append('div').attr('class', 'legend__text').text(function (d) {
      return d.key;
    });

    return pieChart;
  });
}