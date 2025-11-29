/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
let resource = document.querySelector('#resource-form');
// TODO: Select the resources table body ('#resources-tbody').
let resources_table_body = document.querySelector('#resources-tbody');
// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {
  // ... your implementation here ...
  let newtr = document.createElement('tr');

  let newtd1 = document.createElement('td');
  newtd1.textContent=resource.title;

  let newtd2 = document.createElement('td');
  newtd2.textContent=resource.description;

  let newtd3 = document.createElement('td');

  let edit= document.createElement('button');
  edit.textContent= "Edit";
  edit.classList.add("edit-btn");
  edit.dataset.id = resource.id;
  
  let Delete = document.createElement('button');
  Delete.textContent="Delete";
  Delete.classList.add('delete-btn');
  Delete.dataset.id=resource.id;

  newtd3.appendChild(edit);
  newtd3.appendChild(Delete);

  newtr.appendChild(newtd1);
  newtr.appendChild(newtd2);
  newtr.appendChild(newtd3);

  return newtr;

}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  resources_table_body.innerHTML="";

  resources.forEach(function(resource){
    let newresource = createResourceRow(resource);
    resources_table_body.appendChild(newresource);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. Create a new resource object with a unique ID (e.g., `id: \`res_${Date.now()}\``).
 * 4. Add this new resource object to the global `resources` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddResource(event) {
  // ... your implementation here ...
  
    event.preventDefault();

    let title = document.getElementById("resource-title").value ;
    let description = document.getElementById("resource-description").value;
    let link = document.getElementById('resource-link').value;

    let newResource = {
        id: `res_${Date.now()}`,
        title: title,  
        description: description,
        link: link
    };
    resources.push(newResource);
    renderTable();

    let form = document.querySelector('#resource-form');
    form.reset();


  

}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `resources` array by filtering out the resource
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  
    if(event.target.classList("delete-btn")){
        let id = event.target.dataset.ir;
        resources = resources.filter(resource => resource.id !== id);
        renderTable();
    }
 

}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
   try {
        const response = await fetch('resources.json');
        
        resources = await response.json();

        renderTable();

        resource.addEventListener('submit', handleAddResource);

        resources_table_body.addEventListener('click', handleTableClick);

    } catch (error) {
        console.error("Error loading resources:", error);
    }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
