<script>
let dashboardPanelsContainer = document.querySelector('body.index .panels');
let editButtonsContainer = document.querySelector('body.items #edit.panel');
let saveButtonsContainer = document.querySelector('body.items #save.panel');
let url = null;
let identifiers = null;

if(dashboardPanelsContainer){
	let stats = '<?php echo dashboard_init();?>';
	dashboardPanelsContainer.innerHTML = stats + dashboardPanelsContainer.innerHTML;
}
if(editButtonsContainer){
	identifiers = document.querySelectorAll('#dublin-core-identifier .element-text');
}
if(saveButtonsContainer){
	identifiers = document.querySelectorAll('#element-43 textarea');
}
if(identifiers){
	identifiers.forEach((identifier)=>{
		let str = identifier.textContent;
		if(
			str.startsWith('http') &&
			!str.includes('viewcontent')
		){
			// subject to change but this should be the IR link
			url = str;
		}
	});
}
if(url){
	let predecessor = document.querySelector('a.big.blue.button');
	let link = document.createElement('a');
	link.href = url;
	// link.target = '_blank';
	link.classList.add('ir-link','big','button');
	link.textContent = 'View IR Record';
	link.style.background = '#FFFFA6';
	link.style.color = '#665800';
	link.style.borderColor = 'rgba(102, 88, 0, 0.25)';
	if(predecessor){
		predecessor.after(link)
	}else{
		editButtonsContainer.append(link);
	}
}
</script>
<style>
	sup{
		line-height: 0;
	}
</style>