<?php
include_once('Graph.php');

/*
creates the graph data structure that will be used to pass data among components.  Structure must not change
*/
class DemoGraph extends Graph { 

	function __construct() {
		parent::__construct();
			
		// gives the classes of nodes
		$this->data['nodetypes'] = array('animals', 'foods'); //FIXME - add properties for types
		
	  	// gives the classes of edges, and which nodes types they will connect
		$this->data['edgetypes'] = array( 'animal_to_food' => array('animals', 'foods'));
		
		// graph level properties
		$this->data['properties'] = array(
			//sets the scaling of the elements in the gui
			'minSize' => array('animals' => '1', 'foods' => '1', 'animal_to_food'=>'10'),
			'maxSize' => array('animals' => '3', 'foods' => '3', 'animal_to_food' =>'40'),
			'nodeNum' => 25,
			'edgeNum' => 160,
			'log_scaling' => 0

		);
		// special properties for controling layout software
		//BROKEN, USE GV PARAMS
		$this->data['graphvizProperties'] = array(
			'graph'=> array(
				'bgcolor'=>'#FFFFFF',
#				'size' => '6.52,6.52!',
				'fontsize'=>90,
				'splines'=>'true',
				'fontcolor'=>'blue'
			),
			'node'=> array('label'=> ' ', 'imagescale'=>'true','fixedsize'=>1, 'style'=> 'setlinewidth(7), filled', 'regular'=>'true', 'fontsize'=>50),
			'edge'=>array('arrowhead'=>'normal', 'color'=>'#99339966', 'fontsize'=>50)
		);
		srand(20); //Don't copy this - this just makes sure that we are generating the same 'random' values each time
	}

	/**
	There must be a  <nodetype_fetchNodes() function for each defined node type.  
	It returns the ids of the nodes for the type. 
	**/

	function animals_fetchNodes() {
		global $animals;
		$graph = &$this->data;
		$nodes = array();
		foreach (array_rand($animals, $graph['properties']['nodeNum']) as $index) {
			$id = 'animal_'.$index;
			$nodes[$id] = array('id'=>$id);
		}	
		return $nodes;
	}

	function foods_fetchNodes() {
		global $foods;
		$graph = &$this->data;
		$nodes = array();
		foreach (array_rand($foods, $graph['properties']['nodeNum']) as $index) {
			$id = 'food_'.$index;
			$nodes[$id] = array('id'=>$id);
		}
		return $nodes;
	}

	function animals_nodeProperties() {
		global $animals;
		$nodes = $this->getNodesByType('animals');
		foreach ($nodes as &$node) {
			$aid = str_replace('animal_', '', $node['id']);
			$node['label'] = $animals[$aid];
			$node['shape'] = 'house';
			$node['color'] = 'red';
			$node['fillcolor'] = 'pink';
			$node['value'] = rand(0, 20);
			$node['tooltip'] = $animals[$aid]." (".$node['value'].")";
			$node['onClick'] = "this.Framework.selectNode('".$node['id']."'); this.Framework.panToNode('".$node['id']."');";
			$node['onMouseover'] = "this.Framework.highlightNode('".$node['id']."');";
		}
		$nodes = $this->scaleSizes($nodes, 'animals', 'value');
		return $nodes;	
	}

	function foods_nodeProperties() {
		global $foods;
		$nodes = $this->getNodesByType('foods');
		foreach ($nodes as &$node) {
			$fid = str_replace('food_', '', $node['id']);
			$node['tooltip'] = $foods[$fid];
			$node['label'] = $foods[$fid];
			$node['shape'] = 'box';
			$node['color'] = 'black';
			$node['fillcolor'] = "#ccccff";
			$node['value'] = rand(0, 20);
			$node['onClick'] = "this.Framework.selectNode('".$node['id']."'); this.Framework.panToNode('".$node['id']."');";
			$node['onMouseover'] = "this.Framework.highlightNode('".$node['id']."');";
		}
		$nodes = $this->scaleSizes($nodes, 'foods', 'value');
		return $nodes;	
	}

	function animal_to_food_fetchEdges() {
		$graph = &$this->data;
		$animals = $graph['nodetypesindex']['animals'];
		$foods = $graph['nodetypesindex']['foods'];
		$nodes = $graph['nodes'];
		$edges = array();

		for($x=0; $x<= $graph['properties']['edgeNum']; $x++) {
			$animal = $nodes[$animals[array_rand($animals)]];
			$food = $nodes[$foods[array_rand($foods)]];
			$id = $animal['id'].'_'.$food['id'];
			$edge = array(
				'id'=>$id,
				'fromId'=>$animal['id'],
				'toId'=>$food['id']
			);
			$edges[$id] = $edge;
		}
		return $edges;
	}

	function animal_to_food_edgeProperties() {
		global $animals;
		global $foods;
		$edges = $this->getEdgesByType('animal_to_food');
		foreach ($edges as &$edge) {
			$fid = str_replace('food_', '', $edge['toId']);
			$aid = str_replace('animal_', '', $edge['fromId']);
			$edge['value'] = rand(1, 100);
			$edge['weight'] = $edge['value'];
			$edge['label'] = $animals[$aid] . ' eats ' . $edge['value']. ' '. $foods[$fid];
			$edge['tooltip'] = $animals[$aid] . ' eats ' . $edge['value']. ' '. $foods[$fid];
			$edge['onMouseover'] = "this.showTooltip('".$edge['tooltip']."');";
		}
		$edges = $this->scaleSizes($edges, 'animal_to_food', 'value');
		return $edges;
	}
}

$animals = array('Aardvark', 'Addax', 'Alligator', 'Alpaca', 'Anteater', 'Antelope', 'Aoudad', 'Ape', 'Argali', 'Armadillo', 'Ass', 'Baboon', 'Badger', 'Basilisk', 'Bat', 'Bear', 'Beaver', 'Bighorn', 'Bison', 'Boar', 'Budgerigar', 'Buffalo', 'Bull', 'Bunny', 'Burro', 'Camel', 'Canary', 'Capybara', 'Cat', 'Chameleon', 'Chamois', 'Cheetah', 'Chimpanzee', 'Chinchilla', 'Chipmunk', 'Civet', 'Coati', 'Colt', 'Cony', 'Cougar', 'Cow', 'Coyote', 'Crocodile', 'Crow', 'Deer', 'Dingo', 'Doe', 'Dog', 'Donkey', 'Dormouse', 'Dromedary', 'Duckbill', 'Dugong', 'Eland', 'Elephant', 'Elk', 'Ermine', 'Ewe', 'Fawn', 'Ferret', 'Finch', 'Fish', 'Fox', 'Frog', 'Gazelle', 'Gemsbok', 'Gila monster', 'Giraffe', 'Gnu', 'Goat', 'Gopher', 'Gorilla', 'Grizzly bear', 'Ground hog', 'Guanaco', 'Guinea pig', 'Hamster', 'Hare', 'Hartebeest', 'Hedgehog', 'Hippopotamus', 'Hog', 'Horse', 'Hyena', 'Ibex', 'Iguana', 'Impala', 'Jackal', 'Jaguar', 'Jerboa', 'Kangaroo', 'Kid', 'Kinkajou', 'Kitten', 'Koala', 'Koodoo', 'Lamb', 'Lemur', 'Leopard', 'Lion', 'Lizard', 'Llama', 'Lovebird', 'Lynx', 'Mandrill', 'Mare', 'Marmoset', 'Marten', 'Mink', 'Mole', 'Mongoose', 'Monkey', 'Moose', 'Mountain goat', 'Mouse', 'Mule', 'Musk deer', 'Musk-ox', 'Muskrat', 'Mustang', 'Mynah bird', 'Newt', 'Ocelot', 'Okapi', 'Opossum', 'Orangutan', 'Oryx', 'Otter', 'Ox', 'Panda', 'Panther', 'Parakeet', 'Parrot', 'Peccary', 'Pig', 'Platypus', 'Polar bear', 'Pony', 'Porcupine', 'Porpoise', 'Prairie dog', 'Pronghorn', 'Puma', 'Puppy', 'Quagga', 'Rabbit', 'Raccoon', 'Ram', 'Rat', 'Reindeer', 'Reptile', 'Rhinoceros', 'Roebuck', 'Salamander', 'Seal', 'Sheep', 'Shrew', 'Silver fox', 'Skunk', 'Sloth', 'Snake', 'Springbok', 'Squirrel', 'Stallion', 'Steer', 'Tapir', 'Tiger', 'Toad', 'Turtle', 'Vicuna', 'Walrus', 'Warthog', 'Waterbuck', 'Weasel', 'Whale', 'Wildcat', 'Wolf', 'Wolverine', 'Wombat', 'Woodchuck', 'Yak', 'Zebra', 'Zebu'); 

$foods = array('Asparagus','Avocados','Beets','Bell peppers','Broccoli','Brussels sprouts','Cabbage','Carrots','Cauliflower','Celery','Collard greens','Cucumbers','Eggplant','Fennel','Garlic','Green beans','Green peas','Kale','Leeks','Mushrooms, crimini','Mushrooms, shiitake','Mustard greens','Olives','Onions','Potatoes','Romaine lettuce','Sea vegetables','Spinach','Squash, summer','Squash, winter','Sweet potatoes','Swiss chard','Tomatoes','Turnip greens','Yams','Apples','Apricots','Bananas','Blueberries','Cantaloupe','Cranberries','Figs','Grapefruit','Grapes','Kiwifruit','Lemon/Limes','Oranges','Papaya','Pears','Pineapple','Plums','Prunes','Raisins','Raspberries','Strawberries','Watermelon','Cheese','Eggs','Milk, cow','Milk, goat','Yogurt','Black beans','Dried peas','Garbanzo beans (chickpeas)','Kidney beans','Lentils','Lima beans','Miso','Navy beans','Pinto beans','Soybeans','Tempeh','Tofu','Almonds','Cashews','Flaxseeds','Olive oil, extra virgin','Peanuts','Pumpkin seeds','Sesame seeds','Sunflower seeds','Walnuts','Barley','Brown rice','Buckwheat','Corn','Millet','Oats','Quinoa','Rye','Spelt','Whole wheat','Basil','Black pepper','Cayenne pepper','Chili pepper, dried','Cilantro/Coriander seeds','Cinnamon, ground','Cloves','Cumin seeds','Dill','Ginger','Mustard seeds','Oregano','Parsley','Peppermint','Rosemary','Sage','Thyme','Turmeric','Blackstrap molasses','Cane juice','Honey','Maple syrup','Green tea','Soy sauce (tamari)','Water');
