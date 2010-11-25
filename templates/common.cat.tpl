  <p>{L('listnote')}</p>
  <?php
  $countlines = -1;
  $categories = $proj->listCategories(Req::val('list_id'), false, false, false);
  if(count($categories) == 0)
  {
      trigger_error("common.cat.tpl: root category for list ".Req::val('list_id')." missing. We shouldn't be here.");
  } else {
      $root = $categories[0];
      unset($categories[0]);
  }
  ?>
    <form action="{$this->url(array($do, 'proj' . $proj->id, 'list'), array('list_id' => Req::val('list_id')))}" method="post">
      <table class="list" id="listTable">
         <thead>
         <tr>
           <th>{L('name')}</th>
           <th>{L('owner')}</th>
           <th>{L('show')}</th>
           <th>{L('delete')}</th>
         </tr>
       </thead>
       <tbody>
        <?php
        foreach ($categories as $row):
            $countlines++;
        ?>
        <tr class="depth{$row['depth']}">
          <td class="first">
            <input type="hidden" name="lft[]" value="{$row['lft']}" />
            <input type="hidden" name="rgt[]" value="{$row['rgt']}" />
            <input type="hidden" name="id[]" value="{$row['category_id']}" />
            <img onclick="TableControl.up('listTable');" src="{$this->get_image('up')}" class="hide" alt="{L('moveup')}" width="16" height="16" />
            <img onclick="TableControl.down('listTable');" src="{$this->get_image('down')}" class="hide" alt="{L('moveup')}" width="16" height="16" />
            <img onclick="TableControl.shallower('listTable');" src="{$this->get_image('left')}" class="hide" alt="{L('moveleft')}" width="16" height="16" />
            <img onclick="TableControl.deeper('listTable');" src="{$this->get_image('right')}" class="hide" alt="{L('moveright')}" width="16" height="16" />
            <span class="depthmark">{!str_repeat('&rarr;', intval($row['depth']))}</span>
            <input id="categoryname{$countlines}" class="text" type="text" size="15" maxlength="40" name="list_name[]"
              value="{$row['category_name']}" />
          </td>
          <td title="{L('categoryownertip')}">
            {!tpl_userselect('category_owner' . $countlines, $row['category_owner'], 'categoryowner' . $countlines)}
          </td>
          <td title="{L('listshowtip')}">
            {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
          </td>
          <td title="{L('listdeletetip')}">
            <input id="delete{$row['category_id']}" type="checkbox"
                   name="delete[{$row['category_id']}]" value="1" />
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if($countlines > -1): ?>
        <tfoot>
        <tr>
          <td colspan="3"></td>
          <td class="buttons">
            <input type="hidden" name="action" value="update_category" />
            <input type="hidden" name="area" value="list" />
            <input type="hidden" name="list_id" value="{Req::val('list_id')}" />
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <button type="submit">{L('update')}</button>
          </td>
        </tr>
        </tfoot>
        <?php endif; ?>
      </table>
      <?php if (count($categories)): ?>
      <script type="text/javascript">
        <?php
            echo 'TableControl.create("listTable",{
                controlBox: "controlBox",
                tree: true,
                spreadActiveClass: true
            });';
        ?>
      </script>
      <?php endif; ?>
    </form>

    <hr />

    <!-- Form to add a new category to the list -->
    <form action="{$this->url(array($do, 'proj' . $proj->id, 'list'), array('list_id' => Req::val('list_id')))}" method="post">
      <table class="list">
        <tr>
          <td>
            <input id="listnamenew" class="text" type="text" size="15" maxlength="40" name="list_name" />
          </td>
          <td title="{L('categoryownertip')}">
            {!tpl_userselect('category_owner', Post::val('category_owner'), 'categoryownernew')}
          </td>
          <td title="{L('categoryparenttip')}">
            <label for="parent_id">{L('parent')}</label>
            <select id="parent_id" name="parent_id">
              <option value="{$root['category_id']}">{L('notsubcategory')}</option>
              {!tpl_options($proj->listCategories(Req::val('list_id'), false), Post::val('parent_id'))}
            </select>
          </td>
          <td class="buttons">
            <input type="hidden" name="action" value="add_category" />
            <input type="hidden" name="area" value="list" />
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <input type="hidden" name="list_id" value="{Req::val('list_id')}" />
            <button type="submit">{L('addnew')}</button>
          </td>
        </tr>
      </table>