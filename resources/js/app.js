import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;
document.dispatchEvent(new Event('chartjs:ready'));
