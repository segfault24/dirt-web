"use strict";

function loadPublicExchangeContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'', responsivePriority: 2},
			{title:'Location', responsivePriority: 4},
			{title:'Issuer', responsivePriority: 1},
			{title:'Price', responsivePriority: 3},
			{title:'Title', responsivePriority: 5},
			{title:'Date Issued', responsivePriority: 4}
		],
		order: [[5, "desc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/exchange', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">Details</a>',
				result[i].locationName,
				result[i].issuerName,
				formatInt(result[i].price),
				result[i].title,
				result[i].dateIssued
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

function loadPublicFinishedExchangeContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'', responsivePriority: 2},
			{title:'Location', responsivePriority: 4},
			{title:'Issuer', responsivePriority: 1},
			{title:'Acceptor', responsivePriority: 1},
			{title:'Price', responsivePriority: 3},
			{title:'Title', responsivePriority: 5},
			{title:'Last Seen', responsivePriority: 4}
		],
		order: [[6, "desc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/exchange/finished', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">Details</a>',
				result[i].locationName,
				result[i].issuerName,
				result[i].acceptorName,
				formatInt(result[i].price),
				result[i].title,
				result[i].lastSeen
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

function loadPublicCourierContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'', responsivePriority: 2},
			{title:'Issuer', responsivePriority: 4},
			{title:'Start', responsivePriority: 1},
			{title:'End', responsivePriority: 1},
			{title:'Volume', responsivePriority: 2},
			{title:'Collateral', responsivePriority: 3},
			{title:'Reward', responsivePriority: 2},
			{title:'Date Issued', responsivePriority: 5},
			{title:'Days to Complete', responsivePriority: 6}
		],
		order: [[7, "desc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/courier', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">Details</a>',
				result[i].issuerName,
				result[i].startLocation,
				result[i].endLocation,
				formatInt(result[i].volume),
				formatInt(result[i].collateral),
				formatInt(result[i].reward),
				result[i].dateIssued,
				result[i].daysToComplete
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

function loadPublicFinishedCourierContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'', responsivePriority: 2},
			{title:'Issuer', responsivePriority: 4},
			{title:'Start', responsivePriority: 1},
			{title:'End', responsivePriority: 1},
			{title:'Volume', responsivePriority: 2},
			{title:'Collateral', responsivePriority: 3},
			{title:'Reward', responsivePriority: 2},
			{title:'Date Issued', responsivePriority: 5},
			{title:'Acceptor', responsivePriority: 6},
			{title:'Last Seen', responsivePriority: 6}
		],
		order: [[9, "desc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/courier/finished', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">Details</a>',
				result[i].issuerName,
				result[i].startLocation,
				result[i].endLocation,
				formatInt(result[i].volume),
				formatInt(result[i].collateral),
				formatInt(result[i].reward),
				result[i].dateIssued,
				result[i].acceptor,
				result[i].lastSeen
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

function loadPublicOpenAuctionContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
}

function loadPublicFinishedAuctionContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
}

function loadPublicOpenCapContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'Hull Type', responsivePriority: 1},
			{title:'Location', responsivePriority: 2},
			{title:'Contractor', responsivePriority: 5},
			{title:'Price', responsivePriority: 1},
			{title:'Fittings', responsivePriority: 3},
			{title:'Hull Estimate', responsivePriority: 4},
			{title:'Issued', responsivePriority: 5},
			{title:'Description', responsivePriority: 5}
		],
		order: [[5, "asc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/capital', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">' + result[i].typeName + '</a>',
				result[i].locationName,
				result[i].issuer,
				formatInt(result[i].price),
				result[i].fittings !== null ? formatInt(result[i].fittings) : 0,
				result[i].hullvalue !== null ? formatInt(result[i].hullvalue) : formatInt(result[i].price),
				result[i].dateIssued,
				result[i].title
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

function loadPublicFinishedCapContracts() {
	if ($.fn.DataTable.isDataTable('#contracts-table')) {
		$('#contracts-table').DataTable().destroy();
		$('#contracts-table').empty();
	}
	var contractsTable = $('#contracts-table').DataTable({
		columns: [
			{title:'Hull Type', responsivePriority: 1},
			{title:'Location', responsivePriority: 2},
			{title:'Contractor', responsivePriority: 5},
			{title:'Price', responsivePriority: 1},
			{title:'Fittings', responsivePriority: 3},
			{title:'Hull Estimate', responsivePriority: 4},
			{title:'Last Seen', responsivePriority: 5},
			{title:'Description', responsivePriority: 5}
		],
		order: [[6, "desc"]],
		searching: true,
		paging: true,
		pageLength: 25,
		bInfo: false,
		responsive: true,
		select: true
	});
	$.getJSON('/api/contract/public/capital/finished', function(result) {
		for(var i=0; i<result.length; i++) {
			contractsTable.row.add([
				'<a class="open-in-game-contract" data-contractid="' + result[i].contractId + '" href="#"><i class="fa fa-magnet fa-fw"></i></a> <a href="contract/' + result[i].contractId + '" target="_blank">' + result[i].typeName + '</a>',
				result[i].locationName,
				result[i].issuer,
				formatInt(result[i].price),
				result[i].fittings !== null ? formatInt(result[i].fittings) : 0,
				result[i].hullvalue !== null ? formatInt(result[i].hullvalue) : formatInt(result[i].price),
				result[i].lastSeen,
				result[i].title
			]);
		}
		contractsTable.draw();
		$.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
	})
}

