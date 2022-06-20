console.log("You are Offline!");

let user = null;

// Fetch user as JSON (incl. bookmarks)
fetch("/user.json")
  .then(response => {
    return response.json();
  })
  .then(function(json) {

    // Print JSON
    user = json;
    printBookmarks(user.Bookmarks);

    // Load main.js
    var script = document.createElement('script');
    script.src = "assets/js/main.min.js";
    document.head.appendChild(script)


  })

/**
 * 
 * @param {*} bookmarks array
 */
function printBookmarks(bookmarks) {

  const l = bookmarks.length

  for (let i = 0; i < l; ++i) {
    const bookmark = bookmarks[i];

    // Bookmark Wrapper
    const article = document.createElement("article");
    article.classList.add("bookmark");
    article.classList.add("card-background");
    article.classList.add("lazy");
    article.dataset.search = bookmark.title + ";" + bookmark.link + ";" + bookmark.tags;
    article.dataset.tags = bookmark.tags;
    article.setAttribute("brand", bookmark.title.toLowerCase());

    // Bookmark Header 
    const header = document.createElement("header");
    const header_anker = document.createElement("a");
    header_anker.classList.add("card-title");
    header_anker.rel = "noopener noreferrer";
    header_anker.target = "_self";
    header_anker.href = bookmark.link;
    header_anker.innerText = bookmark.title.trim();
    header.appendChild(header_anker);

    // Bookmark Middle
    const middle_anker = document.createElement("a");
    middle_anker.rel = "noopener noreferrer";
    middle_anker.target = "_self";
    middle_anker.href = bookmark.link;
    const middle_anker_span = document.createElement("span");
    middle_anker_span.classList.add("card-spanner");
    middle_anker.appendChild(middle_anker_span);

    // Bookmark Footer
    // const footer = document.createElement("footer");
    // const div = document.createElement("div");
    // div.classList.add("grid");
    // div.classList.add("card-grid");
    // const edit_button = document.createElement("button");
    // edit_button.title = "edit_button Bookmark";
    // edit_button.innerHTML = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d='M490.3 40.4C512.2 62.27 512.2 97.73 490.3 119.6L460.3 149.7L362.3 51.72L392.4 21.66C414.3-.2135 449.7-.2135 471.6 21.66L490.3 40.4zM172.4 241.7L339.7 74.34L437.7 172.3L270.3 339.6C264.2 345.8 256.7 350.4 248.4 353.2L159.6 382.8C150.1 385.6 141.5 383.4 135 376.1C128.6 370.5 126.4 361 129.2 352.4L158.8 263.6C161.6 255.3 166.2 247.8 172.4 241.7V241.7zM192 63.1C209.7 63.1 224 78.33 224 95.1C224 113.7 209.7 127.1 192 127.1H96C78.33 127.1 64 142.3 64 159.1V416C64 433.7 78.33 448 96 448H352C369.7 448 384 433.7 384 416V319.1C384 302.3 398.3 287.1 416 287.1C433.7 287.1 448 302.3 448 319.1V416C448 469 405 512 352 512H96C42.98 512 0 469 0 416V159.1C0 106.1 42.98 63.1 96 63.1H192z'/></svg>";
    // edit_button.disabled = true;
    // div.appendChild(edit_button);

    // let tags = bookmark.tags.split(",");
    // tags.forEach((item, index) => {
    //   const tag_span = document.createElement("span");
    //   tag_span.classList.add("tag");
    //   tag_span.dataset.tag = item.trim();
    //   tag_span.onclick = "toggleTag(this.getAttribute('data-tag'))";
    //   tag_span.innerText = item.trim();
    //   div.appendChild(tag_span);
    // });

    // const delete_button = document.createElement("button");
    // delete_button.title = "Delete Bookmark";
    // delete_button.innerHTML = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d='M135.2 17.69C140.6 6.848 151.7 0 163.8 0H284.2C296.3 0 307.4 6.848 312.8 17.69L320 32H416C433.7 32 448 46.33 448 64C448 81.67 433.7 96 416 96H32C14.33 96 0 81.67 0 64C0 46.33 14.33 32 32 32H128L135.2 17.69zM31.1 128H416V448C416 483.3 387.3 512 352 512H95.1C60.65 512 31.1 483.3 31.1 448V128zM111.1 208V432C111.1 440.8 119.2 448 127.1 448C136.8 448 143.1 440.8 143.1 432V208C143.1 199.2 136.8 192 127.1 192C119.2 192 111.1 199.2 111.1 208zM207.1 208V432C207.1 440.8 215.2 448 223.1 448C232.8 448 240 440.8 240 432V208C240 199.2 232.8 192 223.1 192C215.2 192 207.1 199.2 207.1 208zM304 208V432C304 440.8 311.2 448 320 448C328.8 448 336 440.8 336 432V208C336 199.2 328.8 192 320 192C311.2 192 304 199.2 304 208z'/></svg>";
    // delete_button.disabled = true;
    // div.appendChild(delete_button);
    // footer.appendChild(div);

    article.appendChild(header);
    article.appendChild(middle_anker);
    // article.appendChild(footer);

    document.getElementById("bookmarks").appendChild(article);
  }
}