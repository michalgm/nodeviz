# an outline of the process of fetching a graph

# Overview of process #

## Requesting a network ##
When a webpage is going to display a graph using the framework, it goes through the following process:

  1. The HTML page on the client loads Graph.js and initializes a graph object.
  1. The JS sends an AJAX request to the server (request.php), including an parameters describing the network to be built.
  1. Request.php checks that there is an appropriate graph initialization file for the graph type being requested. If caching is enabled, and the files matching the parameters have all ready been created, the cached files are sent back.
  1. A php graph object is created with arrays for nodes, edges, and meta properties of the graph
  1. The graph initialization file interprets the parameters and makes assembles data for nodes, node properties, edges, and edge properties into the graph object.  Often these data are pulled from a database
  1. After the graph file is assembled, it written out in JSON format and cached to disk to be sent back to client.
  1. The graph file is also written out in GraphViz ".dot" format, and fed through the GraphViz **neato** or **dot** programs to calculate layout positions for all the nodes and edges.
  1. GraphViz also renders SVG and JPG versions of the network image, and caches them to disk.
  1. Request.php sends the the JSON and SVG interpretations of the network back to the client.
  1. When the Graph.js on the client receives the response, it loads the network and its properties into memory.
  1. It also inserts the SVG into the designated element on the page and attaches the appropriate event listeners.
  1. It also (optionally) walks through the graph structure and generates html for a text-based nested list description of the graph, and attaches listeners to it.




# A more detailed explanation #