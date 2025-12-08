/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
const listSection = document.getElementById("resource-list-section");

// --- Functions ---

/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
  const article = document.createElement("article");

  const h2 = document.createElement("h2");
  h2.textContent = resource.title;

  const p = document.createElement("p");
  p.textContent = resource.description;

  const link = document.createElement("a");
  link.textContent = "View Resource & Discussion";
  link.href = `details.html?id=${resource.id}`;

  article.appendChild(h2);
  article.appendChild(p);
  article.appendChild(link);

  return article;
}

/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadResources() {
  try {
    const response = await fetch("api/resources.json");
    const resources = await response.json();

    listSection.innerHTML = "";

    resources.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });
  } catch (error) {
    listSection.innerHTML = "<p>Error loading resources.</p>";
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
