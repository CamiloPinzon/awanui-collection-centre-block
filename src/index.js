import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import Save from "./save";
import "./style.scss";
import "./editor.scss";

registerBlockType("awanui/collection-centre", {
	title: "Awanui Collection Centre",
	icon: "location",
	category: "widgets",
	attributes: {
		centreId: { type: "string", default: "" },
		centreData: { type: "object", default: null },
	},
	edit: Edit,
	save: Save,
});
