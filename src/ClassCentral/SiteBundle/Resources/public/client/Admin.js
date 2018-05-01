class QuillEditor {
  constructor() {
    $(document).ready(() => {
      if (!window.Quill) {
        return;
      }

      const toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike', 'image', 'link'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'header': [1, 2, 3, false] }],
        [{ 'align': [] }],
      ];

      this.quillEditor = new window.Quill("#editor", {
        theme: "snow",
        modules: {
          toolbar: toolbarOptions,
        }
      });

      this.quillEditor.getModule('toolbar').addHandler("image", () => {
        this.selectImage();
      });

      this.handleFormSubmit();
    });
  }

  handleFormSubmit() {
    $("form[name=classcentral_sitebundle_helpguidearticle]").submit(() => {
      $('#classcentral_sitebundle_helpguidearticle_text').text(this.quillEditor.root.innerHTML);
    });
    $("form[name=classcentral_sitebundle_helpguidesection]").submit(() => {
      $('#classcentral_sitebundle_helpguidesection_description').text(this.quillEditor.root.innerHTML);
    });
  }

  selectImage() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.click();

    // Listen upload local image and save to server
    input.onchange = () => {
      const file = input.files[0];

      // file type is only image.
      if (/^image\//.test(file.type)) {
        this.embedLoadingPlaceholder();
        this.retrieveAWSUrl(file);
      } else {
        alert('You could only upload images.');
      }
    };
  }

  retrieveAWSUrl(file) {
    $.ajax({
      "url": "/admin/help-guides/get-image-upload-url",
      "data": {
        "content-type": file.type,
        "ext": file.type.replace("image/", ""),
      },
      dataType: "json",
    })
    .done((result) => {
      this.imageUrl = result.message.imageUrl;
      this.uploadToS3(file, result.message.signedUrl);
    });
  }

  uploadToS3(file, signedUrl) {
    $.ajax({
      method: "PUT",
      url: signedUrl,
      data: file,
      headers: {
        "Content-Type": file.type,
      },
      processData: false,
      xhr: () => {
        const xhr = $.ajaxSettings.xhr();
        if (xhr.upload) {
          xhr.upload.addEventListener('progress', function(event){
            let percent = 0;
            const position = event.loaded || event.position;
            const total = event.total;
            if (event.lengthComputable) {
              percent = Math.ceil(position / total * 100);
            }
            $("#editor").find("[data-editor-loading] p").html(`${percent}%`);
          }, true);
        }
        return xhr;
      }
    }).done(() => {
      this.embedImage();
    });
  };

  embedImage() {
    $("#editor").find("[data-editor-loading]").remove();
    const range = this.quillEditor.getSelection();
    this.quillEditor.insertEmbed(range.index, "image", this.imageUrl);
  }

  embedLoadingPlaceholder() {
    $('#editor').append('<div data-editor-loading class="absolute top left width-100 height-100 bg-white transparent">' +
      '<p class="text-1 text--bold text-center width-100 absolute" style="top: 45%">Uploading...</p>' +
    '</div>');
  }
}

new QuillEditor();
