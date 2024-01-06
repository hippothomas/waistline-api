const copyText = (e) => {
	let input = e.target.closest(".infobox").querySelector("input[disabled]");
  	input.select();
	input.setSelectionRange(0, 99999); // For mobile devices

	let currentTarget = e.currentTarget;
	// Copy the text inside the text field
	navigator.clipboard.writeText(input.value).then(() => {
  		currentTarget.setAttribute("tooltip", "Copied!");
	},() => {
  		currentTarget.setAttribute("tooltip", "Failed to copy...");
	});
};
const resetTooltip = (e) => {
	e.currentTarget.setAttribute("tooltip", "Copy to clipboard");
};

const copyButtons = document.getElementsByClassName("copylink");
Array.from(copyButtons).forEach(function(copyButton) {
	copyButton.addEventListener("click", (e) => copyText(e));
	copyButton.addEventListener("mouseover", (e) => resetTooltip(e));
});
